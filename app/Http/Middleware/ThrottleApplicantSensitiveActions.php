<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleApplicantSensitiveActions
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user?->role === 'user') {
            $keys = [
                'applicant-settings-user:'.$user->id,
                'applicant-settings-ip:'.$request->ip(),
            ];

            foreach ($keys as $key) {
                if (RateLimiter::tooManyAttempts($key, 5)) {
                    abort(429, 'Too many account changes. Please wait a minute and try again.');
                }
            }

            foreach ($keys as $key) {
                RateLimiter::hit($key, 60);
            }
        }

        return $next($request);
    }
}
