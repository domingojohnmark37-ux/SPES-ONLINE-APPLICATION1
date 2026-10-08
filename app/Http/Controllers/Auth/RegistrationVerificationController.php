<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationVerificationCode;
use App\Models\PendingRegistration;
use App\Models\AuditAction;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegistrationVerificationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $pending = $this->pendingRegistration($request);

        if (! $pending) {
            return redirect()->route('register')
                ->with('status', 'Start sign-up again to request a verification code.');
        }

        return view('auth.verify-registration', [
            'email' => $pending->email,
            'resendAvailableAt' => $pending->last_sent_at?->addSeconds(60)->timestamp,
            'resendsRemaining' => max(0, 3 - $pending->resend_count),
        ]);
    }

    public function verify(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $request->validate([
            'pin' => ['required', 'string', 'max:32'],
        ]);

        $token = $request->session()->get('pending_registration_token');
        $pin = (string) $request->input('pin');

        [$result, $user] = DB::transaction(function () use ($token, $pin, $auditLogger): array {
            $pending = PendingRegistration::where('token', $token)
                ->lockForUpdate()
                ->first();

            if (! $pending || $pending->expires_at->isPast()) {
                return ['unavailable', null];
            }

            if ($pending->failed_attempts >= 5 || $pending->pin_hash === null) {
                return ['invalid', null];
            }

            $pinIsValid = preg_match('/^\d{6}$/D', $pin) === 1
                && $pending->pin_expires_at?->isFuture()
                && Hash::check($pin, $pending->pin_hash);

            if (! $pinIsValid) {
                $pending->failed_attempts++;
                if ($pending->failed_attempts >= 5) {
                    $pending->pin_hash = null;
                }
                $pending->save();

                return ['invalid', null];
            }

            if (User::where('email', $pending->email)->exists()) {
                $pending->delete();

                return ['already_exists', null];
            }

            $emailName = Str::before($pending->email, '@');
            $usernameBase = Str::slug($emailName, '_') ?: 'user';
            $username = $usernameBase;
            $usernameSuffix = 1;

            while (User::where('username', $username)->exists()) {
                $username = $usernameBase.'_'.$usernameSuffix++;
            }

            $user = User::create([
                'name' => Str::of($emailName)->replace(['.', '_', '-'], ' ')->title()->toString(),
                'username' => $username,
                'email' => $pending->email,
                'password' => $pending->password_hash,
                'role' => 'user',
            ]);
            $user->markEmailAsVerified();
            $auditLogger->record(
                AuditAction::ACCOUNT_CREATED,
                'Authentication',
                $user,
                $user,
                null,
                ['description' => 'Applicant account created after email verification'],
            );

            $pending->delete();

            return ['verified', $user];
        });

        if ($result === 'verified' && $user) {
            $request->session()->forget([
                'pending_registration_token',
                'pending_registration_email',
            ]);
            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->put('show_application_status_toasts', true);

            return redirect()->route('dashboard');
        }

        if ($result === 'already_exists') {
            $request->session()->forget([
                'pending_registration_token',
                'pending_registration_email',
            ]);

            return redirect()->route('login')
                ->with('status', 'Verification could not complete sign-up. Please sign in or try again.');
        }

        if ($result === 'unavailable') {
            $request->session()->forget([
                'pending_registration_token',
                'pending_registration_email',
            ]);

            return redirect()->route('register')
                ->with('status', 'The verification request is no longer available. Please start sign-up again.');
        }

        return back()->withErrors([
            'pin' => 'The verification code is invalid or expired. Request a new code to continue.',
        ]);
    }

    public function resend(Request $request): RedirectResponse
    {
        if (! $this->verificationMailerIsSafe()) {
            return back()->withErrors([
                'pin' => 'Email verification is unavailable. Please try again later.',
            ]);
        }

        $token = $request->session()->get('pending_registration_token');
        $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        [$result, $pending] = DB::transaction(function () use ($token, $pin): array {
            $pending = PendingRegistration::where('token', $token)
                ->lockForUpdate()
                ->first();

            if (! $pending || $pending->expires_at->isPast()) {
                return ['unavailable', null];
            }

            if ($pending->resend_count >= 3) {
                return ['limit', $pending];
            }

            $resendAvailableAt = $pending->last_sent_at?->addSeconds(60);
            if ($resendAvailableAt && $resendAvailableAt->isFuture()) {
                return ['cooldown', $pending];
            }

            $pending->pin_hash = Hash::make($pin);
            $pending->pin_expires_at = now()->addMinutes(10);
            $pending->failed_attempts = 0;
            $pending->resend_count++;
            $pending->last_sent_at = now();
            $pending->save();

            return ['resent', $pending];
        });

        if ($result === 'unavailable') {
            $request->session()->forget([
                'pending_registration_token',
                'pending_registration_email',
            ]);

            return redirect()->route('register')
                ->with('status', 'The verification request is no longer available. Please start sign-up again.');
        }

        if ($result === 'limit') {
            return back()->withErrors([
                'pin' => 'No more verification codes can be sent for this sign-up. Start again to request another code.',
            ]);
        }

        if ($result === 'cooldown') {
            return back()->withErrors([
                'pin' => 'Please wait 60 seconds before requesting another code.',
            ]);
        }

        Mail::to($pending->email)->send(new RegistrationVerificationCode($pin));

        return back()->with('status', 'If the email is eligible, a new verification code has been sent.');
    }

    private function pendingRegistration(Request $request): ?PendingRegistration
    {
        $token = $request->session()->get('pending_registration_token');

        return $token
            ? PendingRegistration::where('token', $token)->where('expires_at', '>', now())->first()
            : null;
    }

    private function verificationMailerIsSafe(): bool
    {
        if (app()->environment('testing')) {
            return true;
        }

        $mailer = (string) config('mail.default');
        $fallbackMailers = config("mail.mailers.{$mailer}.mailers", []);

        return ! in_array($mailer, ['log', 'array'], true)
            && ! in_array('log', $fallbackMailers, true);
    }
}
