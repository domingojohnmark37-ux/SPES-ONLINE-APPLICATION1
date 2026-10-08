<?php

namespace Tests\Feature\Auth;

use App\Models\ApplicantSetting;
use App\Models\User;
use App\Notifications\ApplicantLoginAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
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

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
