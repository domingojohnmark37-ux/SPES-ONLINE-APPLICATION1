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
            $limits = [
                'applicant-settings-user:'.$user->id => 5,
                'applicant-settings-ip:'.$request->ip() => 30,
            ];

            foreach ($limits as $key => $limit) {
                if (RateLimiter::tooManyAttempts($key, $limit)) {
                    abort(429, 'Too many account changes. Please wait a minute and try again.');
                }
            }

            foreach ($limits as $key => $limit) {
                RateLimiter::hit($key, 60);
            }
        }

        return $next($request);
    }
}
