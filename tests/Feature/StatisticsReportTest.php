<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\DemographicReportArchive;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\StatisticsReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class StatisticsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_statistics_report_uses_application_records_and_renders_empty_state(): void
    {
        $admin = User::factory()->admin()->create();

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
        $admin = User::factory()->admin()->create();
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
        $applicant = User::factory()->create([]);

        $this->actingAs($applicant)
            ->get(route('admin.statistics-report'))
            ->assertForbidden();
    }

    public function test_configured_application_window_is_retrieved_from_system_settings(): void
    {
        $admin = User::factory()->admin()->create();
        SystemSetting::create([
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
        $admin = User::factory()->admin()->create();
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

    public function test_age_demographics_use_the_configured_age_group_ranges(): void
    {
        foreach ([15, 17, 18, 20, 21, 24, 25, 30] as $age) {
            Application::factory()->create([
                'age' => $age,
                'created_at' => now(),
            ]);
        }

        $ageGroups = app(StatisticsReportService::class)
            ->report(['date_range' => 'all'])['demographics']['age']['items'];

        $this->assertSame([
            ['label' => '15–17', 'count' => 2, 'percentage' => 25.0],
            ['label' => '18–20', 'count' => 2, 'percentage' => 25.0],
            ['label' => '21–24', 'count' => 2, 'percentage' => 25.0],
            ['label' => '25–30', 'count' => 2, 'percentage' => 25.0],
        ], $ageGroups);
    }

    public function test_admin_can_generate_download_and_archive_approved_demographic_report(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $birthday = '2006-03-10';
        Application::factory()->create([
            'full_name' => 'John Doe',
            'first_name' => 'John',
            'surname' => 'Doe',
            'birthday' => $birthday,
            'age' => 20,
            'status' => 'approved',
            'sex' => 'Male',
            'barangay' => 'Bical',
            'education' => 'College (Currently Enrolled)',
            'grade_year_level' => '1st year college',
            'created_at' => '2026-10-02 09:00:00',
        ]);
        Application::factory()->create([
            'full_name' => ' john   doe ',
            'first_name' => 'John',
            'surname' => 'Doe',
            'birthday' => $birthday,
            'age' => 20,
            'status' => 'approved',
            'sex' => 'Male',
            'barangay' => 'Bical',
            'education' => 'College (Currently Enrolled)',
            'grade_year_level' => '1st year college',
            'created_at' => '2026-10-03 09:00:00',
        ]);
        Application::factory()->create([
            'full_name' => 'Jane Smith',
            'first_name' => 'Jane',
            'surname' => 'Smith',
            'birthday' => '2004-02-10',
            'age' => 22,
            'status' => 'approved',
            'sex' => 'Female',
            'barangay' => 'Dagupan',
            'education' => 'Senior High School',
            'grade_year_level' => 'Grade 12',
            'created_at' => '2026-10-04 09:00:00',
        ]);
        Application::factory()->create([
            'status' => 'pending',
            'created_at' => '2026-10-05 09:00:00',
        ]);
        Application::factory()->create([
            'status' => 'approved',
            'created_at' => '2026-11-01 09:00:00',
        ]);
        Application::factory()->create([
            'status' => 'approved',
            'created_at' => '2026-09-15 09:00:00',
            'full_name' => 'Previous Period Applicant',
            'birthday' => '2005-04-12',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.demographic-reports.store'), [
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
                'program_year' => 2026,
                'requested_by' => 'PESO Admin',
                '_idempotency_token' => Str::random(40),
            ])
            ->assertRedirect(route('admin.statistics-report'))
            ->assertSessionHas('success');

        $report = DemographicReportArchive::query()->firstOrFail();
        $this->assertSame(6, $report->total_received);
        $this->assertSame(2, $report->outside_date_range);
        $this->assertSame(1, $report->not_approved);
        $this->assertSame(1, $report->duplicates_removed);
        $this->assertSame(2, $report->total_valid);
        $this->assertSame('Draft', $report->status);
        $this->assertSame('PESO Admin', $report->requested_by);
        $this->assertSame(3, $report->report_data['approved_in_period']);
        $this->assertSame('2026-10-01', $report->report_data['start_date']);
        $this->assertSame('2026-10-31', $report->report_data['end_date']);
        $this->assertSame(1, $report->report_data['comparison']['total_valid']);
        $this->assertSame(1, $report->report_data['comparison']['difference']);
        $this->assertEquals(100.0, $report->report_data['comparison']['percentage_change']);
        $this->assertStringContainsString('unique approved applicants', $report->report_data['summary']);
        $this->assertStringContainsString('a change of +1 (100.0%)', $report->report_data['summary']);
        $this->assertNotSame([], $report->report_data['key_findings']);
        $this->assertNotSame([], $report->report_data['recommendations']);
        $this->assertStringNotContainsString('John Doe', $report->report_data['summary']);
        foreach ($report->report_data['demographics'] as $distribution) {
            $this->assertSame(2, array_sum(array_column($distribution['items'], 'count')));
        }

        $this->assertNull($report->docx_path);
        Storage::disk('local')->assertExists($report->pdf_path);
        $pdfContents = Storage::disk('local')->get($report->pdf_path);
        $this->assertStringStartsWith('%PDF-', $pdfContents);
        $this->assertMatchesRegularExpression('/\/MediaBox\s+\[0(?:\.0+)?\s+0(?:\.0+)?\s+595\.28\d*\s+841\.89\d*\]/', $pdfContents);
        $this->get(route('admin.demographic-reports.download', [$report, 'pdf']))->assertOk();
        $this->get(route('admin.demographic-reports.download', [$report, 'docx']))->assertNotFound();
        $this->get('/admin/statistics-report/demographic-reports/archive-index')->assertMethodNotAllowed();
        $this->get(route('admin.statistics-report'))
            ->assertOk()
            ->assertDontSee('Download Excel index')
            ->assertDontSee('>Word</a>', false)
            ->assertDontSee('Mark Final')
            ->assertSee('Download PDF')
            ->assertSee('Delete')
            ->assertSee('data-admin-confirm-message="Delete this archived report and its PDF? This action cannot be undone."', false)
            ->assertDontSee('onsubmit="return confirm(', false)
            ->assertSee('SPES_Demographics_2026-10-01_to_2026-10-31_v1.pdf');

        $this->actingAs($admin)
            ->post(route('admin.demographic-reports.store'), [
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
                'program_year' => 2026,
                '_idempotency_token' => Str::random(40),
            ])
            ->assertRedirect(route('admin.statistics-report'));
        $secondReport = DemographicReportArchive::query()->where('version', 2)->firstOrFail();
        $this->assertNull($secondReport->docx_path);
        Storage::disk('local')->assertMissing('SPES Demographic Reports Archive/SPES_Demographics_2026-10-01_to_2026-10-31_v1.docx');
        Storage::disk('local')->assertMissing('SPES Demographic Reports Archive/SPES_Demographic_Report_Archive_Index.xlsx');

        $applicant = User::factory()->create([]);
        $this->actingAs($applicant)
            ->delete(route('admin.demographic-reports.destroy', $secondReport))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.demographic-reports.destroy', $secondReport))
            ->assertRedirect(route('admin.statistics-report'))
            ->assertSessionHas('success', 'The demographic report and its archived PDF were deleted.');
        $this->assertDatabaseMissing('demographic_report_archives', ['id' => $secondReport->id]);
        Storage::disk('local')->assertMissing($secondReport->pdf_path);
        $this->assertDatabaseHas('demographic_report_archives', ['id' => $report->id]);
    }

    public function test_demographic_report_generation_requires_valid_dates_and_admin_role(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->post(route('admin.demographic-reports.store'), [
                'start_date' => '2026-11-02',
                'end_date' => '2026-11-01',
                'program_year' => 2026,
            ])
            ->assertSessionHasErrors(['end_date']);

        $applicant = User::factory()->create([]);
        $this->actingAs($applicant)
            ->post(route('admin.demographic-reports.store'), [
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
                'program_year' => 2026,
            ])
            ->assertForbidden();
    }
}
