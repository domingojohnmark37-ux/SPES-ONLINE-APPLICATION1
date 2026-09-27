<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_records_last_activity(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->fresh()->last_active_at);
        $this->assertSame('Active', $user->fresh()->activityStatus());
    }

    public function test_authenticated_request_and_logout_refresh_last_activity(): void
    {
        $user = User::factory()->create(['last_active_at' => now()->subDays(3)]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->assertTrue($user->fresh()->last_active_at->greaterThan(now()->subMinute()));

        $this->post(route('logout'))->assertRedirect('/');
        $this->assertTrue($user->fresh()->last_active_at->greaterThan(now()->subMinute()));
    }

    public function test_activity_status_is_inactive_after_one_year(): void
    {
        $user = User::factory()->create(['last_active_at' => now()->subYears(2)]);

        $this->assertSame('Inactive', $user->activityStatus());
        $this->assertStringStartsWith('Logged out ', $user->activityDescription());
    }

    public function test_pending_application_status_remains_pending_for_inactive_users(): void
    {
        $user = User::factory()->create(['last_active_at' => now()->subYears(2)]);

        $this->assertSame('Pending', $user->displayStatus('pending'));
        $this->assertSame('Inactive', $user->displayStatus('denied'));
    }

    public function test_applicant_dashboard_shows_grouped_quick_reply_help_without_text_entry(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('SPES Applicant Help')
            ->assertSee('Application')
            ->assertSee('Documents')
            ->assertSee('After approval')
            ->assertSee('How do I apply?')
            ->assertSee('data-answer=', false)
            ->assertDontSee('id="portalHelpInput"', false);
    }
}