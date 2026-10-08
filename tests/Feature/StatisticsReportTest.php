<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_statistics_report_uses_application_records_and_renders_empty_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.statistics-report'))
            ->assertOk()
            ->assertSee('Statistics Report')
            ->assertSee(route('admin.statistics-report'), false)
            ->assertSee('No application data is available for the selected filters.')
            ->assertSee('Academic/program year filtering is unavailable')
            ->assertSee('Municipality is not stored on applications');

        Application::factory()->create([
            'status' => 'approved',
            'barangay' => 'Bical',
            'sex' => 'Female',
            'birthday' => now()->subYears(19)->toDateString(),
            'education' => 'College (Currently Enrolled)',
            'created_at' => now()->subMonths(2),
        ]);
        Application::factory()->create([
            'status' => 'pending',
            'barangay' => 'Bical',
            'sex' => 'Male',
            'birthday' => now()->subYears(21)->toDateString(),
            'education' => 'Senior High School Graduate',
            'created_at' => now()->subMonth(),
        ]);
        Application::factory()->create([
            'status' => 'denied',
            'barangay' => 'Alibago',
            'sex' => 'Female',
            'created_at' => now()->subDays(10),
        ]);

        $this->get(route('admin.statistics-report', ['date_range' => 'all']))
            ->assertOk()
            ->assertSee('3', false)
            ->assertSee('66.7%', false)
            ->assertSee('33.3%', false)
            ->assertSee('Bical')
            ->assertSee('Alibago')
            ->assertSee('College (Currently Enrolled)')
            ->assertSee('Application Period Comparison');
    }

    public function test_report_filters_recalculate_totals_and_chart_from_matching_applications(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Application::factory()->create([
            'status' => 'approved',
            'barangay' => 'Bical',
            'created_at' => now()->subDays(5),
        ]);
        Application::factory()->create([
            'status' => 'pending',
            'barangay' => 'Bical',
            'created_at' => now()->subDays(4),
        ]);
        Application::factory()->create([
            'status' => 'approved',
            'barangay' => 'Alibago',
            'created_at' => now()->subDays(3),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.statistics-report', [
                'date_range' => 'all',
                'barangay' => 'Bical',
            ]))
            ->assertOk()
            ->assertSee('2', false)
            ->assertSee('50.0%', false)
            ->assertSee('Alibago');

        $this->get(route('admin.statistics-report', [
            'date_range' => 'all',
            'barangay' => 'Bical',
            'status' => 'approved',
        ]))
            ->assertOk()
            ->assertSee('1', false)
            ->assertSee('100.0%', false);

        $this->get(route('admin.statistics-report', [
            'date_range' => 'custom',
            'date_from' => now()->subDays(5)->toDateString(),
            'date_to' => now()->subDays(4)->toDateString(),
        ]))
            ->assertOk()
            ->assertSee('2', false);
    }

    public function test_only_administrators_can_access_statistics_report(): void
    {
        $applicant = User::factory()->create(['role' => 'user']);

        $this->actingAs($applicant)
            ->get(route('admin.statistics-report'))
            ->assertForbidden();
    }

    public function test_configured_application_window_is_retrieved_from_system_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        \App\Models\SystemSetting::create([
            'application_start_date' => '2026-09-30 09:00:00',
            'application_end_date' => '2026-10-15 17:00:00',
        ]);
        Application::factory()->create([
            'status' => 'approved',
            'created_at' => '2026-10-02 10:00:00',
        ]);
        Application::factory()->create([
            'status' => 'pending',
            'created_at' => '2026-09-20 10:00:00',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.statistics-report', ['period' => 'configured', 'date_range' => 'all']))
            ->assertOk()
            ->assertSee('Configured application window')
            ->assertSee('1', false)
            ->assertSee('100.0%');
    }

    public function test_custom_date_range_compares_with_the_equivalent_previous_interval(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Application::factory()->create([
            'status' => 'denied',
            'created_at' => now()->subDays(3),
        ]);
        Application::factory()->create([
            'status' => 'approved',
            'created_at' => now()->subDay(),
        ]);
        Application::factory()->create([
            'status' => 'pending',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.statistics-report', [
                'date_range' => 'custom',
                'date_from' => now()->subDay()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('2', false)
            ->assertSee('Previous comparison window')
            ->assertSee('100.0% vs. previous period');
    }
}
