<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\PersonalizedSecurityVerification;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, AuditLogger $auditLogger): RedirectResponse|JsonResponse
    {
        $user = $request->authenticate($auditLogger);

        $request->session()->regenerate();

        $request->session()->forget([
            'personal_security_pending_user_id',
            'personal_security_pending_expires_at',
            'personal_security_pending_remember',
            'personal_security_recovery_required',
        ]);

        $securityVerification = null;
        if (Schema::hasTable('personalized_security_verifications')) {
            $securityVerification = PersonalizedSecurityVerification::query()
                ->where('user_id', $user->id)
                ->whereNotNull('enabled_at')
                ->first();
        } else {
            Log::warning('Personal security verification is unavailable because its migration has not been applied.', [
                'user_id' => $user->id,
            ]);
        }
        if ($securityVerification !== null) {
            $request->session()->put([
                'personal_security_pending_user_id' => $user->id,
                'personal_security_pending_expires_at' => now()->addMinutes(15)->timestamp,
                'personal_security_pending_remember' => $request->boolean('remember'),
            ]);

            $challengeUrl = route('login.personal-security.challenge', absolute: false);
            if ($request->expectsJson()) {
                return response()->json(['redirect' => $challengeUrl]);
            }

            return redirect()->route('login.personal-security.challenge');
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $this->completeLogin($request, $user);

        $redirect = $this->redirectToRoleSafeDestination($request, $user);

        if ($request->expectsJson()) {
            return response()->json(['redirect' => $redirect->getTargetUrl()]);
        }

        return $redirect;
    }

    public function completeVerifiedLogin(Request $request, User $user, bool $remember): RedirectResponse
    {
        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();
        $this->completeLogin($request, $user);

        return $this->redirectToRoleSafeDestination($request, $user);
    }

    private function redirectToRoleSafeDestination(Request $request, User $user): RedirectResponse
    {
        $destination = $user->role === 'admin'
            ? route('admin.dashboard', absolute: false)
            : route('dashboard', absolute: false);
        $intendedUrl = $request->session()->get('url.intended');
        $intendedPath = is_string($intendedUrl) ? parse_url($intendedUrl, PHP_URL_PATH) : null;
        $intendedHost = is_string($intendedUrl) ? parse_url($intendedUrl, PHP_URL_HOST) : null;
        $sameHost = $intendedHost === null || strcasecmp($intendedHost, $request->getHost()) === 0;
        $isAdminPath = is_string($intendedPath) && preg_match('/^\/admin(?:\/|$)/', $intendedPath) === 1;
        $canAccessIntendedPath = $sameHost
            && is_string($intendedPath)
            && ($user->role === 'admin' ? $isAdminPath : ! $isAdminPath);

        if ($intendedUrl !== null && ! $canAccessIntendedPath) {
            $request->session()->forget('url.intended');
        }

        return redirect()->intended($destination);
    }

    private function completeLogin(Request $request, User $user): void
    {
        $user->forceFill(['last_active_at' => now()])->save();

        if ($user->role === 'user') {
            $request->session()->put('show_application_status_toasts', true);
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()?->forceFill(['last_active_at' => now()])->save();
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
