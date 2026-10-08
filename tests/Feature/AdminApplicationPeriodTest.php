<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Models\AuditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApplicationPeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_application_status_and_quick_open_close_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        SystemSetting::current()->update([
            'application_start_date' => now()->subDay(),
            'application_end_date' => now()->addDay(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Current submission status')
            ->assertSee('Applications are open')
            ->assertSee('Application schedule')
            ->assertSee('Keep applications open')
            ->assertSee('Close applications now')
            ->assertSee('Changes take effect when saved.')
            ->assertSee('Save application schedule')
            ->assertSee('Cancel')
            ->assertSee('Confirm schedule changes')
            ->assertSee('Yes, save changes')
            ->assertSee('Leave without saving?')
            ->assertSee('Stay and keep editing')
            ->assertSee('beforeunload')
            ->assertSee('data-unsaved-guard="off"', false)
            ->assertSee('aria-live="polite"', false);
    }

    public function test_admin_can_schedule_the_application_window(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $start = now()->addDays(2)->startOfMinute();
        $end = now()->addDays(9)->startOfMinute();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'application_start_date' => $start->format('Y-m-d\TH:i'),
                'application_end_date' => $end->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $settings = SystemSetting::current()->fresh();
        $this->assertSame($start->format('Y-m-d H:i'), $settings->application_start_date->format('Y-m-d H:i'));
        $this->assertSame($end->format('Y-m-d H:i'), $settings->application_end_date->format('Y-m-d H:i'));
        $this->assertDatabaseCount('audit_logs', 2);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditAction::APPLICATION_PERIOD_UPDATED,
            'module' => 'Application Period',
            'field_name' => 'application_start_date',
            'old_value' => null,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditAction::APPLICATION_PERIOD_UPDATED,
            'module' => 'Application Period',
            'field_name' => 'application_end_date',
            'old_value' => null,
        ]);
    }

    public function test_admin_can_review_schedule_changes_and_earlier_later_statistics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $originalStart = now()->addDays(5)->startOfMinute();
        $originalEnd = now()->addDays(15)->startOfMinute();
        SystemSetting::current()->update([
            'application_start_date' => $originalStart,
            'application_end_date' => $originalEnd,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'application_start_date' => $originalStart->copy()->subDays(2)->format('Y-m-d\TH:i'),
                'application_end_date' => $originalEnd->copy()->addDays(3)->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('audit_logs', 2);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'field_name' => 'application_start_date',
            'old_value' => $originalStart->format('Y-m-d H:i:s'),
            'new_value' => $originalStart->copy()->subDays(2)->format('Y-m-d H:i:s'),
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'field_name' => 'application_end_date',
            'old_value' => $originalEnd->format('Y-m-d H:i:s'),
            'new_value' => $originalEnd->copy()->addDays(3)->format('Y-m-d H:i:s'),
        ]);

        $this->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Application schedule audit history')
            ->assertSee('Moved earlier')
            ->assertSee('Moved later')
            ->assertSee('2.0 days total')
            ->assertSee('3.0 days total')
            ->assertSee($admin->name)
            ->assertSee('Start of submission')
            ->assertSee('End of submission');
    }

    public function test_saving_an_unchanged_schedule_does_not_create_audit_entries(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $start = now()->addDays(2)->startOfMinute();
        $end = now()->addDays(9)->startOfMinute();
        SystemSetting::current()->update([
            'application_start_date' => $start,
            'application_end_date' => $end,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'application_start_date' => $start->format('Y-m-d\TH:i'),
                'application_end_date' => $end->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_admin_cannot_save_a_closing_time_before_the_opening_time(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.settings'))
            ->put(route('admin.settings.update'), [
                'application_start_date' => now()->addDays(3)->format('Y-m-d\TH:i'),
                'application_end_date' => now()->addDays(2)->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHasErrors('application_end_date');
    }
}
