<?php

namespace App\Http\Middleware;

use App\Models\AdminPreference;
use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApplyAdminPreferences
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->role !== 'admin') {
            return $next($request);
        }

        $preference = AdminPreference::firstOrNew(['user_id' => $user->id]);
        $user->setRelation('adminPreference', $preference);
        $previousTimezone = date_default_timezone_get();
        $previousAppTimezone = config('app.timezone');
        $previousLocale = app()->getLocale();
        $timezone = $preference->timezone ?: 'Asia/Manila';

        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
        app()->setLocale($preference->language === 'fil' ? 'fil' : 'en');

        try {
            $timeoutMinutes = (int) (SystemSetting::query()->value('admin_session_timeout_minutes') ?: 30);
            $lastActivity = (int) $request->session()->get('admin_last_activity_at', 0);

            if ($lastActivity > 0 && now()->timestamp - $lastActivity >= $timeoutMinutes * 60) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Your admin session expired due to inactivity. Please sign in again.',
                ]);
            }

            $request->session()->put('admin_last_activity_at', now()->timestamp);

            return $next($request);
        } finally {
            config(['app.timezone' => $previousAppTimezone]);
            date_default_timezone_set($previousTimezone);
            app()->setLocale($previousLocale);
        }
    }
}
