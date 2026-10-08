<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Validation\Rules\Password;
use App\Listeners\RecordApplicantLogin;
use App\Listeners\RecordAuditLogout;
use App\Listeners\RecordAuditLockout;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('adminDate', function (string $expression): string {
            return "<?php echo e(app(\\App\\Support\\AdminDateFormatter::class)->format({$expression})); ?>";
        });

        Event::listen(Login::class, RecordApplicantLogin::class);
        Event::listen(Logout::class, RecordAuditLogout::class);
        Event::listen(Lockout::class, RecordAuditLockout::class);
        Gate::define('viewApplicantAudit', fn ($user): bool => $user->role === 'admin');

        RateLimiter::for('applicant-sensitive', function (Request $request) {
            return [
                Limit::perMinute(5)->by('applicant-settings-ip:'.$request->ip()),
                Limit::perMinute(5)->by('applicant-settings-user:'.$request->user()?->id),
            ];
        });

        RateLimiter::for('registration', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(5)->by('registration-ip:'.$request->ip()),
                Limit::perMinute(5)->by('registration-email:'.hash('sha256', $email)),
            ];
        });

        RateLimiter::for('registration-verification', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->session()->get('pending_registration_email', '')));

            return [
                Limit::perMinute(5)->by('verification-ip:'.$request->ip()),
                Limit::perMinute(5)->by('verification-email:'.hash('sha256', $email)),
            ];
        });

        Password::defaults(fn () => Password::min(12)
            ->numbers()
            ->uncompromised()
            ->rules(function ($attribute, $value, $fail) {
                if (! is_string($value)) {
                    return;
                }

                if (! preg_match('/\p{Lu}/u', $value)) {
                    $fail('The password must contain at least one uppercase letter.');
                }

                if (! preg_match('/[\p{S}\p{P}]/u', $value)) {
                    $fail('The password must contain at least one symbol.');
                }
            }));
    }
}
