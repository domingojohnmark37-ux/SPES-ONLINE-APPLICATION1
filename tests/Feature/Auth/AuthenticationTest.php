<?php

namespace Tests\Feature\Auth;

use App\Models\ApplicantSetting;
use App\Models\User;
use App\Notifications\ApplicantLoginAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertHeader('Pragma', 'no-cache')
            ->assertSee('data-auth-form="login"', false)
            ->assertSee('data-loading-label="Signing in..."', false)
            ->assertSee('data-auth-spinner', false)
            ->assertSee('data-auth-request-status', false)
            ->assertSee('data-auth-submit', false)
            ->assertSee('data-recovery-url=', false)
            ->assertSee('data-csrf-refresh-url=', false);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_applicant_login_does_not_redirect_to_an_intended_admin_route(): void
    {
        $user = User::factory()->create();
        Queue::fake();

        $this->get(route('admin.settings'))
            ->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_admin_login_does_not_redirect_to_an_intended_applicant_route(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Queue::fake();

        $this->get(route('applications.create'))
            ->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard', absolute: false));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_can_sign_in_even_if_personal_security_migrations_are_pending(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Schema::dropIfExists('pending_personal_security_recoveries');
        Schema::dropIfExists('personalized_security_verifications');
        Queue::fake();

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard', absolute: false));
        $this->assertAuthenticatedAs($admin);
        Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job): bool => $job->notification instanceof ApplicantLoginAlert);
    }

    public function test_admin_login_alert_is_queued_instead_of_sending_mail_during_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Queue::fake();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard', absolute: false));

        $this->assertAuthenticatedAs($admin);
        Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job): bool => $job->notification instanceof ApplicantLoginAlert);
    }

    public function test_login_can_return_a_json_redirect_for_the_async_auth_form(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk();
        $this->assertSame(
            route('dashboard', absolute: false),
            parse_url($response->json('redirect'), PHP_URL_PATH),
        );
        $this->assertAuthenticatedAs($user);
    }

    public function test_auth_recovery_reports_the_current_session_destination(): void
    {
        $this->getJson(route('auth.recovery'))
            ->assertOk()
            ->assertJson(['authenticated' => false]);

        $user = User::factory()->create();
        $this->actingAs($user)
            ->getJson(route('auth.recovery'))
            ->assertOk()
            ->assertJson([
                'authenticated' => true,
                'redirect' => route('dashboard', absolute: false),
            ]);
    }

    public function test_unverified_users_can_sign_in_and_receive_an_account_alert(): void
    {
        $user = User::factory()->unverified()->create();
        Notification::fake();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))->assertOk();
        Notification::assertSentTo(
            $user,
            ApplicantLoginAlert::class,
            fn (ApplicantLoginAlert $notification, array $channels): bool => in_array('mail', $channels, true),
        );
    }

    public function test_applicant_can_disable_email_sign_in_alerts(): void
    {
        $user = User::factory()->create();
        ApplicantSetting::create([
            'user_id' => $user->id,
            'login_notifications' => false,
        ]);
        Notification::fake();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        Notification::assertSentTo(
            $user,
            ApplicantLoginAlert::class,
            fn (ApplicantLoginAlert $notification, array $channels): bool => ! in_array('mail', $channels, true),
        );
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_login_ip_limiter_applies_across_different_emails(): void
    {
        config(['auth.login_ip_max_attempts' => 3]);

        foreach (['first', 'second', 'third'] as $emailPrefix) {
            $this->postJson('/login', [
                'email' => "{$emailPrefix}@example.com",
                'password' => 'wrong-password',
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('email');
        }

        $response = $this->postJson('/login', [
            'email' => 'fourth@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertNotSame(trans('auth.failed'), $response->json('errors.email.0'));
    }

    public function test_async_login_reports_invalid_credentials_as_json_validation_errors(): void
    {
        $user = User::factory()->create();

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
