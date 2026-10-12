<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PersonalSecurityRecoveryCode;
use App\Models\AuditAction;
use App\Models\PendingPersonalSecurityRecovery;
use App\Models\PersonalizedSecurityVerification;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\PersonalSecurityAnswer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PersonalSecurityVerificationController extends Controller
{
    public function challenge(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);
        if ($user === null) {
            $this->clearPendingLogin($request);

            return redirect()->route('login')->with('status', __('Sign in again to continue.'));
        }

        $verification = PersonalizedSecurityVerification::query()
            ->where('user_id', $user->id)
            ->whereNotNull('enabled_at')
            ->first();
        if ($verification === null) {
            $this->clearPendingLogin($request);

            return redirect()->route('login')->with('status', __('Sign in again to continue.'));
        }

        return view('auth.personal-security-challenge', [
            'question' => $verification->question,
        ]);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->clearPendingLogin($request);
        $request->session()->forget('url.intended');

        return redirect()->route('login');
    }

    public function verify(
        Request $request,
        AuditLogger $auditLogger,
        AuthenticatedSessionController $sessions,
    ): RedirectResponse {
        $request->validate([
            'security_answer' => ['required', 'string', 'max:255'],
        ]);

        $user = $this->pendingUser($request);
        if ($user === null) {
            $this->clearPendingLogin($request);

            return redirect()->route('login')->with('status', __('Sign in again to continue.'));
        }

        $verification = PersonalizedSecurityVerification::query()
            ->where('user_id', $user->id)
            ->whereNotNull('enabled_at')
            ->first();
        if ($verification === null) {
            $this->clearPendingLogin($request);

            return redirect()->route('login')->with('status', __('Sign in again to continue.'));
        }

        $keys = $this->verificationRateLimitKeys($user, $request);
        if ($this->tooManyAttempts($keys, 5)) {
            return back()->withErrors([
                'security_answer' => __('Verification is temporarily locked. Try again later or use account recovery.'),
            ]);
        }

        $normalizedAnswer = PersonalSecurityAnswer::normalize(
            $request->string('security_answer')->toString(),
        );
        if (! Hash::check(hash('sha256', $normalizedAnswer), $verification->answer_hash)) {
            $this->hitRateLimitKeys($keys, 900);
            $auditLogger->record(
                AuditAction::PERSONAL_SECURITY_FAILED,
                'Personal Security Verification',
                $user,
                $user->role === 'user' ? $user : null,
                null,
                [
                    'result' => 'failed',
                    'description' => 'Personal security verification answer was incorrect',
                    'ip_address' => $request->ip(),
                ],
            );
            Log::notice('Personal security verification failed.', [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
            ]);

            return back()->withErrors([
                'security_answer' => __('The answer could not be verified. Check it and try again, or use account recovery.'),
            ]);
        }

        $this->clearRateLimitKeys($keys);
        $remember = (bool) $request->session()->pull('personal_security_pending_remember', false);
        $this->clearPendingLogin($request);

        return $sessions->completeVerifiedLogin($request, $user, $remember);
    }

    public function recovery(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);
        if ($user === null) {
            $this->clearPendingLogin($request);

            return redirect()->route('login')->with('status', __('Sign in again to continue.'));
        }

        return view('auth.personal-security-recovery');
    }

    public function recoverySetup(Request $request): View|RedirectResponse
    {
        if (! filter_var($request->session()->get('personal_security_recovery_required', false), FILTER_VALIDATE_BOOLEAN)) {
            return redirect()->intended(
                $request->user()->role === 'admin' ? route('admin.dashboard') : route('dashboard'),
            );
        }

        return view('auth.personal-security-required');
    }

    public function sendRecoveryCode(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if ($user === null) {
            $this->clearPendingLogin($request);

            return redirect()->route('login')->with('status', __('Sign in again to continue.'));
        }

        if (! $user->hasVerifiedEmail()) {
            return back()->withErrors([
                'recovery' => __('The account email must be verified before it can be used for recovery. Contact support if you cannot access a verified email.'),
            ]);
        }

        $keys = $this->recoverySendRateLimitKeys($user, $request);
        if ($this->tooManyAttempts($keys, 3)) {
            return back()->withErrors([
                'recovery' => __('A recovery code was requested recently. Try again later.'),
            ]);
        }
        $this->hitRateLimitKeys($keys, 600);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        PendingPersonalSecurityRecovery::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(10),
                'failed_attempts' => 0,
            ],
        );

        try {
            Mail::to($user->email)->send(new PersonalSecurityRecoveryCode($code));
        } catch (Throwable $exception) {
            PendingPersonalSecurityRecovery::query()->where('user_id', $user->id)->delete();
            Log::error('Personal security recovery email could not be delivered.', [
                'user_id' => $user->id,
                'exception' => class_basename($exception),
            ]);

            return back()->withErrors([
                'recovery' => __('The recovery code could not be sent. Please try again later or contact support.'),
            ]);
        }

        $auditLogger->record(
            AuditAction::PERSONAL_SECURITY_RECOVERY,
            'Personal Security Verification',
            $user,
            $user->role === 'user' ? $user : null,
            null,
            ['description' => 'Email recovery code requested'],
        );

        return back()->with('status', __('A recovery code was sent to the email address on your account. It expires in 10 minutes.'));
    }

    public function verifyRecoveryCode(
        Request $request,
        AuditLogger $auditLogger,
        AuthenticatedSessionController $sessions,
    ): RedirectResponse {
        $request->validate([
            'recovery_code' => ['required', 'regex:/\A[0-9]{6}\z/D'],
        ]);

        $user = $this->pendingUser($request);
        if ($user === null) {
            $this->clearPendingLogin($request);

            return redirect()->route('login')->with('status', __('Sign in again to continue.'));
        }

        $keys = $this->recoveryVerifyRateLimitKeys($user, $request);
        $recovery = PendingPersonalSecurityRecovery::query()
            ->where('user_id', $user->id)
            ->first();

        if (
            $this->tooManyAttempts($keys, 5)
            || $recovery === null
            || $recovery->expires_at->isPast()
            || $recovery->failed_attempts >= 5
        ) {
            return back()->withErrors([
                'recovery_code' => __('The recovery code could not be verified. Request a new code or sign in again.'),
            ]);
        }

        if (! Hash::check($request->string('recovery_code')->toString(), $recovery->code_hash)) {
            $this->hitRateLimitKeys($keys, 900);
            $recovery->increment('failed_attempts');
            $auditLogger->record(
                AuditAction::PERSONAL_SECURITY_FAILED,
                'Personal Security Verification',
                $user,
                $user->role === 'user' ? $user : null,
                null,
                [
                    'result' => 'failed',
                    'description' => 'Personal security email recovery code was incorrect',
                    'ip_address' => $request->ip(),
                ],
            );
            Log::notice('Personal security recovery code verification failed.', [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
            ]);

            if ($recovery->fresh()->failed_attempts >= 5 || $this->tooManyAttempts($keys, 5)) {
                $recovery->delete();
            }

            return back()->withErrors([
                'recovery_code' => __('The recovery code could not be verified. Request a new code or sign in again.'),
            ]);
        }

        $this->clearRateLimitKeys($keys);
        PersonalizedSecurityVerification::query()->where('user_id', $user->id)->delete();
        $recovery->delete();
        $remember = (bool) $request->session()->pull('personal_security_pending_remember', false);
        $this->clearPendingLogin($request);
        $auditLogger->record(
            AuditAction::PERSONAL_SECURITY_RECOVERY,
            'Personal Security Verification',
            $user,
            $user->role === 'user' ? $user : null,
            null,
            ['description' => 'Personal security challenge reset after email verification'],
        );
        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();
        $request->session()->put('personal_security_recovery_required', true);

        if ($user->role === 'user') {
            $request->session()->put('show_application_status_toasts', true);

            return redirect()->route('personal-security.recovery-setup')
                ->with('status', __('Set a new personal security question before continuing.'));
        }

        return redirect()->route('admin.personal-security.recovery-setup')
            ->with('status', __('Set a new personal security question before continuing.'));
    }

    public function updateConfiguration(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'question' => ['required', 'string', 'min:8', 'max:255'],
            'security_answer' => ['required', 'string', 'min:8', 'max:255'],
            'security_answer_confirmation' => ['required', 'string', 'min:8', 'max:255'],
            'current_password' => ['required', 'string'],
        ]);

        $answer = PersonalSecurityAnswer::normalize($validated['security_answer']);
        $confirmation = PersonalSecurityAnswer::normalize($validated['security_answer_confirmation']);
        $question = trim($validated['question']);
        if (mb_strlen($question, 'UTF-8') < 8) {
            throw ValidationException::withMessages([
                'question' => __('The security question must contain at least 8 non-space characters.'),
            ]);
        }
        if (mb_strlen($answer, 'UTF-8') < 8) {
            throw ValidationException::withMessages([
                'security_answer' => __('The answer must contain at least 8 non-space characters.'),
            ]);
        }
        if (! hash_equals($answer, $confirmation)) {
            throw ValidationException::withMessages([
                'security_answer_confirmation' => __('The two answers must match.'),
            ]);
        }
        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('The current password is incorrect.'),
            ]);
        }

        $verification = PersonalizedSecurityVerification::query()
            ->where('user_id', $user->id)
            ->first();
        $isUpdate = $verification !== null;
        PersonalizedSecurityVerification::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'question' => $question,
                'answer_hash' => Hash::make(hash('sha256', $answer)),
                'enabled_at' => now(),
            ],
        );

        $auditLogger->record(
            $isUpdate ? AuditAction::PERSONAL_SECURITY_UPDATED : AuditAction::PERSONAL_SECURITY_SETUP,
            'Personal Security Verification',
            $user,
            $user->role === 'user' ? $user : null,
            null,
            ['description' => $isUpdate ? 'Personal security question updated' : 'Personal security question enabled'],
        );
        $request->session()->forget('personal_security_recovery_required');

        return back()->with(
            $user->role === 'user' ? 'settings_success' : 'success',
            __('Personal security verification has been enabled.'),
        );
    }

    public function disable(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'disable_confirmation' => ['required', 'in:DISABLE'],
        ]);
        $user = $request->user();
        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('The current password is incorrect.'),
            ]);
        }

        $verification = PersonalizedSecurityVerification::query()
            ->where('user_id', $user->id)
            ->first();
        if ($verification !== null) {
            $verification->delete();
            $auditLogger->record(
                AuditAction::PERSONAL_SECURITY_DISABLED,
                'Personal Security Verification',
                $user,
                $user->role === 'user' ? $user : null,
                null,
                ['description' => 'Personal security question disabled'],
            );
        }

        return back()->with(
            $user->role === 'user' ? 'settings_success' : 'success',
            __('Personal security verification has been disabled.'),
        );
    }

    private function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get('personal_security_pending_user_id');
        $expiresAt = $request->session()->get('personal_security_pending_expires_at');
        if (! is_numeric($userId) || ! is_numeric($expiresAt) || (int) $expiresAt <= now()->timestamp) {
            return null;
        }

        return User::query()->find((int) $userId);
    }

    private function clearPendingLogin(Request $request): void
    {
        $request->session()->forget([
            'personal_security_pending_user_id',
            'personal_security_pending_expires_at',
            'personal_security_pending_remember',
        ]);
    }

    private function verificationRateLimitKeys(User $user, Request $request): array
    {
        return [
            'personal-security-answer:user:'.$user->id,
            'personal-security-answer:ip:'.hash('sha256', $request->ip()),
        ];
    }

    private function recoverySendRateLimitKeys(User $user, Request $request): array
    {
        return [
            'personal-security-recovery-send:user:'.$user->id,
            'personal-security-recovery-send:ip:'.hash('sha256', $request->ip()),
        ];
    }

    private function recoveryVerifyRateLimitKeys(User $user, Request $request): array
    {
        return [
            'personal-security-recovery-verify:user:'.$user->id,
            'personal-security-recovery-verify:ip:'.hash('sha256', $request->ip()),
        ];
    }

    private function tooManyAttempts(array $keys, int $maximum): bool
    {
        foreach ($keys as $key) {
            if (RateLimiter::tooManyAttempts($key, $maximum)) {
                return true;
            }
        }

        return false;
    }

    private function hitRateLimitKeys(array $keys, int $decaySeconds): void
    {
        foreach ($keys as $key) {
            RateLimiter::hit($key, $decaySeconds);
        }
    }

    private function clearRateLimitKeys(array $keys): void
    {
        foreach ($keys as $key) {
            RateLimiter::clear($key);
        }
    }
}
