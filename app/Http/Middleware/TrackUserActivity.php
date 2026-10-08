<?php

namespace App\Http\Middleware;

use App\Models\ApplicantLoginActivity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $user?->forceFill(['last_active_at' => now()])->save();

        if ($user && $request->session()->has('applicant_login_activity_id')) {
            ApplicantLoginActivity::where('user_id', $user->id)
                ->whereKey($request->session()->pull('applicant_login_activity_id'))
                ->update(['session_id' => $request->session()->getId()]);
        }

        return $next($request);
    }
}