<?php

namespace App\Http\Middleware;

use App\Models\ApplicantSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyApplicantLanguage
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user?->role === 'user') {
            $language = ApplicantSetting::query()
                ->where('user_id', $user->id)
                ->value('language');

            $language = $language === 'fil' ? 'fil' : 'en';
            $previousLocale = app()->getLocale();
            app()->setLocale($language);
            $request->attributes->set('applicant_language', $language);

            try {
                return $next($request);
            } finally {
                app()->setLocale($previousLocale);
            }
        }

        return $next($request);
    }
}
