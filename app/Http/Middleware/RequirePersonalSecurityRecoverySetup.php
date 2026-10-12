<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePersonalSecurityRecoverySetup
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! filter_var($request->session()->get('personal_security_recovery_required', false), FILTER_VALIDATE_BOOLEAN)) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), [
            'personal-security.recovery-setup',
            'personal-security.configuration.update',
            'personal-security.configuration.disable',
            'admin.personal-security.recovery-setup',
            'admin.personal-security.configuration.update',
            'admin.personal-security.configuration.disable',
            'logout',
        ], true)) {
            return $next($request);
        }

        $route = $request->user()?->role === 'admin'
            ? 'admin.personal-security.recovery-setup'
            : 'personal-security.recovery-setup';

        return redirect()->route($route);
    }
}
