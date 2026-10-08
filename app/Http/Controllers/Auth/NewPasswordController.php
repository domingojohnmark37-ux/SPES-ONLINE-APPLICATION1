<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetVerificationCode;
use App\Models\PendingPasswordReset;
use App\Models\User;
use App\Services\AdminPasswordPolicy;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('password_reset_flow_token')
            || ! $request->session()->has('password_reset_email')) {
            return redirect()->route('password.request');
        }

        if ((bool) $request->session()->get('password_reset_otp_sent')) {
            return redirect()->route('password.verify');
        }

        return view('auth.reset-password', [
            'email' => $request->session()->get('password_reset_email'),
        ]);
    }

    public function store(
        Request $request,
        AdminPasswordPolicy $adminPasswordPolicy,
    ): RedirectResponse {
        $email = $request->session()->get('password_reset_email');
        $flowToken = $request->session()->get('password_reset_flow_token');
        if (! is_string($email) || ! is_string($flowToken)) {
            return redirect()->route('password.request');
        }

        $user = User::query()->where('email', $email)->first();
        $passwordRule = $user?->role === 'admin'
            ? $adminPasswordPolicy->rule()
            : Rules\Password::defaults();

        $request->validate([
            'password' => ['required', 'confirmed', $passwordRule],
        ]);

        if (! $user) {
            return back()->withErrors([
                'email' => 'This password reset request cannot be completed. Check the email address and start again.',
            ]);
        }

        if (! $this->verificationMailerIsSafe()) {
            return back()->withErrors([
                'email' => 'Password reset verification email is unavailable. Please try again later.',
            ]);
        }

        $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        DB::transaction(function () use ($user, $flowToken, $request, $pin): void {
            PendingPasswordReset::query()->where('user_id', $user->id)->delete();
            PendingPasswordReset::create([
                'token' => $flowToken,
                'user_id' => $user->id,
                'password_hash' => Hash::make($request->string('password')->toString()),
                'pin_hash' => Hash::make($pin),
                'pin_expires_at' => now()->addMinutes(10),
                'last_sent_at' => now(),
                'failed_attempts' => 0,
                'resend_count' => 0,
                'expires_at' => now()->addMinutes(30),
            ]);
        });

        Mail::to($user->email)->send(new PasswordResetVerificationCode($pin));
        $request->session()->put('password_reset_otp_sent', true);

        return redirect()->route('password.verify')
            ->with('status', 'A 6-digit confirmation code has been sent to your email. The code expires in 10 minutes.');
    }

    public function showVerification(Request $request): View|RedirectResponse
    {
        $pending = $this->pendingReset($request);
        if (! $pending || ! (bool) $request->session()->get('password_reset_otp_sent')) {
            $request->session()->forget([
                'password_reset_flow_token',
                'password_reset_email',
                'password_reset_otp_sent',
            ]);

            return redirect()->route('password.request')
                ->with('status', 'Start the password reset process again to request a new confirmation code.');
        }

        return view('auth.verify-password-reset', [
            'email' => $pending->user->email,
            'resendAvailableAt' => $pending->last_sent_at?->addSeconds(60)->timestamp,
            'resendsRemaining' => max(0, 3 - $pending->resend_count),
        ]);
    }

    public function verify(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $request->validate([
            'pin' => ['required', 'regex:/^\d{6}$/D'],
        ]);

        $token = $request->session()->get('password_reset_flow_token');
        $pin = (string) $request->input('pin');

        [$result, $user] = DB::transaction(function () use ($token, $pin, $auditLogger): array {
            $pending = PendingPasswordReset::query()
                ->where('token', $token)
                ->lockForUpdate()
                ->first();

            if (! $pending || $pending->expires_at->isPast()) {
                return ['unavailable', null];
            }

            if ($pending->failed_attempts >= 5
                || $pending->pin_hash === null
                || $pending->pin_expires_at?->isPast()) {
                return ['invalid', null];
            }

            if (! Hash::check($pin, $pending->pin_hash)) {
                $pending->failed_attempts++;
                if ($pending->failed_attempts >= 5) {
                    $pending->pin_hash = null;
                }
                $pending->save();

                return ['invalid', null];
            }

            $user = User::query()->whereKey($pending->user_id)->lockForUpdate()->first();
            if (! $user) {
                $pending->delete();

                return ['unavailable', null];
            }

            $user->forceFill([
                'password' => $pending->password_hash,
                'remember_token' => Str::random(60),
            ])->save();
            $auditLogger->recordPasswordChanged($user);
            $pending->delete();

            return ['verified', $user];
        });

        if ($result === 'verified' && $user) {
            event(new PasswordReset($user));
            $request->session()->forget([
                'password_reset_flow_token',
                'password_reset_email',
                'password_reset_otp_sent',
            ]);

            return redirect()->route('login')
                ->with('status', 'Your password has been reset successfully. You can now sign in.');
        }

        if ($result === 'unavailable') {
            $request->session()->forget([
                'password_reset_flow_token',
                'password_reset_email',
                'password_reset_otp_sent',
            ]);

            return redirect()->route('password.request')
                ->with('status', 'The confirmation request expired or is no longer available. Start again to reset your password.');
        }

        return back()->withErrors([
            'pin' => 'The confirmation code is invalid, expired, or has reached its attempt limit.',
        ]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $token = $request->session()->get('password_reset_flow_token');
        if (! $this->verificationMailerIsSafe()) {
            return back()->withErrors([
                'pin' => 'Password reset verification email is unavailable. Please try again later.',
            ]);
        }

        $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        [$result, $pending] = DB::transaction(function () use ($token, $pin): array {
            $pending = PendingPasswordReset::query()
                ->where('token', $token)
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
            $pending->last_sent_at = now();
            $pending->failed_attempts = 0;
            $pending->resend_count++;
            $pending->save();

            return ['resent', $pending];
        });

        if ($result === 'unavailable') {
            $request->session()->forget([
                'password_reset_flow_token',
                'password_reset_email',
                'password_reset_otp_sent',
            ]);

            return redirect()->route('password.request')
                ->with('status', 'The confirmation request expired. Start again to reset your password.');
        }

        if ($result === 'limit') {
            return back()->withErrors([
                'pin' => 'No more confirmation codes can be sent for this request. Start again to reset your password.',
            ]);
        }

        if ($result === 'cooldown') {
            return back()->withErrors([
                'pin' => 'Please wait 60 seconds before requesting another code.',
            ]);
        }

        Mail::to($pending->user->email)->send(new PasswordResetVerificationCode($pin));

        return back()->with('status', 'A new confirmation code has been sent to your email.');
    }

    private function pendingReset(Request $request): ?PendingPasswordReset
    {
        $token = $request->session()->get('password_reset_flow_token');

        return is_string($token)
            ? PendingPasswordReset::query()
                ->with('user')
                ->where('token', $token)
                ->where('expires_at', '>', now())
                ->first()
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
