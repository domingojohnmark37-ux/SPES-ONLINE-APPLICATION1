<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\ApplicantAuditQuery;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ApplicantAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_view_applicant_audit(): void
    {
        $applicant = User::factory()->create(['role' => 'user']);

        $this->actingAs($applicant)
            ->get(route('admin.applicant-audit.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.applicant-audit.index'))
            ->assertOk()
            ->assertSee('Applicant Audit Logs')
            ->assertSee('No applicant audit records found.');
    }

    public function test_applicant_audit_contains_applicant_activity_and_admin_audit_stays_separate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'user']);

        app(AuditLogger::class)->record(
            AuditAction::ADMIN_PREFERENCES_UPDATED,
            'Admin Preferences',
            $admin,
        );
        app(AuditLogger::class)->record(
            AuditAction::INFORMATION_UPDATED,
            'Applicant Information',
            $applicant,
            $applicant,
        );

        $response = $this->actingAs($admin)
            ->get(route('admin.preferences'))
            ->assertOk()
            ->assertSee('Admin Audit Logs')
            ->assertSee('Admin Preferences Updated')
            ->assertDontSee('Information Updated');

        $this->assertSame(1, substr_count($response->getContent(), '<td>Admin Preferences Updated</td>'));
        $this->get(route('admin.applicant-audit.index'))
            ->assertOk()
            ->assertSee('Recent Applicant Activity')
            ->assertSee('All applicants')
            ->assertSee('Information Updated');
    }

    public function test_applicant_activity_feed_can_be_filtered_by_applicant(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'user']);
        $otherApplicant = User::factory()->create(['role' => 'user']);

        app(AuditLogger::class)->record(
            AuditAction::LOGIN_SUCCESS,
            'Authentication',
            $applicant,
            $applicant,
        );
        app(AuditLogger::class)->record(
            AuditAction::LOGIN_SUCCESS,
            'Authentication',
            $otherApplicant,
            $otherApplicant,
        );

        $response = $this->actingAs($admin)
            ->get(route('admin.applicant-audit.index', ['user_id' => $applicant->id]))
            ->assertOk()
            ->assertSee($applicant->name);

        $this->assertSame(1, substr_count($response->getContent(), '<strong>'.AuditAction::LOGIN_SUCCESS.'</strong>'));
    }

    public function test_applicant_activity_feed_shows_five_recent_events_and_can_show_all(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'user']);

        for ($index = 0; $index < 7; $index++) {
            app(AuditLogger::class)->record(
                AuditAction::LOGIN_SUCCESS,
                'Authentication',
                $applicant,
                $applicant,
            );
        }

        $recentResponse = $this->actingAs($admin)
            ->get(route('admin.applicant-audit.index'))
            ->assertOk()
            ->assertSee('Show more activities');

        $this->assertSame(5, substr_count($recentResponse->getContent(), '<strong>'.AuditAction::LOGIN_SUCCESS.'</strong>'));

        $allResponse = $this->get(route('admin.applicant-audit.index', ['show_all' => 1]))
            ->assertOk()
            ->assertSee('Show recent 5');

        $this->assertSame(7, substr_count($allResponse->getContent(), '<strong>'.AuditAction::LOGIN_SUCCESS.'</strong>'));
    }

    public function test_applicant_audit_log_table_shows_five_recent_rows_and_can_show_all(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        for ($index = 0; $index < 7; $index++) {
            $applicant = User::factory()->create(['role' => 'user']);
            app(AuditLogger::class)->record(
                AuditAction::ACCOUNT_CREATED,
                'Authentication',
                $applicant,
                $applicant,
            );
        }

        $recentResponse = $this->actingAs($admin)
            ->get(route('admin.applicant-audit.index'))
            ->assertOk()
            ->assertSee('Show more audit logs');

        $this->assertSame(5, substr_count($recentResponse->getContent(), 'class="audit-applicant"'));

        $allResponse = $this->get(route('admin.applicant-audit.index', ['show_all_audit_logs' => 1]))
            ->assertOk()
            ->assertSee('Show recent 5');

        $this->assertSame(7, substr_count($allResponse->getContent(), 'class="audit-applicant"'));
    }

    public function test_applicant_activity_details_are_shown_inline_in_applicant_audit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'user']);
        $event = app(AuditLogger::class)->record(
            AuditAction::INFORMATION_UPDATED,
            'Applicant Information',
            $applicant,
            $applicant,
            null,
            [
                'field_name' => 'mobile_number',
                'old_value' => '09170000000',
                'new_value' => '09171111111',
                'description' => 'Applicant contact information changed',
                'ip_address' => '127.0.0.1',
            ],
        );

        $this->actingAs($admin)
            ->get(route('admin.applicant-audit.index', ['event_id' => $event->id]))
            ->assertOk()
            ->assertSee('Activity Details')
            ->assertSee($event->audit_code)
            ->assertSee('Performed By')
            ->assertSee('Role')
            ->assertSee('Date &amp; Time', false)
            ->assertSee('Applicant Information')
            ->assertSee('09170000000')
            ->assertSee('09171111111')
            ->assertSee('Applicant contact information changed')
            ->assertSee('127.0.0.1');
    }

    public function test_applicant_audit_filters_rows_and_summary_using_database_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'user']);
        $application = Application::factory()->create([
            'user_id' => $applicant->id,
            'status' => 'approved',
            'full_name' => $applicant->name,
        ]);

        app(AuditLogger::class)->record(
            AuditAction::INFORMATION_UPDATED,
            'Applicant Information',
            $applicant,
            $applicant,
            $application,
            ['field_name' => 'barangay', 'old_value' => 'Bical', 'new_value' => 'Alibago'],
        );
        app(AuditLogger::class)->record(
            AuditAction::APPLICATION_APPROVED,
            'Application',
            $admin,
            $applicant,
            $application,
            ['field_name' => 'status', 'old_value' => 'pending', 'new_value' => 'approved'],
        );

        $response = $this->actingAs($admin)
            ->get(route('admin.applicant-audit.index', ['action' => AuditAction::APPLICATION_APPROVED]))
            ->assertOk()
            ->assertSee($applicant->name)
            ->assertSee('Status Changes')
            ->assertSee('<strong>1</strong><span>Status Changes', false)
            ->assertDontSee('<th>Events</th>', false)
            ->assertDontSee('Export Audit');

        $this->get(route('admin.applicant-audit.show', [
            'applicant' => $applicant->id,
            'application_id' => $application->id,
        ]))
            ->assertOk()
            ->assertSee('Audit History')
            ->assertSee(AuditAction::APPLICATION_APPROVED);

        $event = AuditLog::query()->where('action', AuditAction::APPLICATION_APPROVED)->firstOrFail();
        $this->get(route('admin.applicant-audit.event', $event))
            ->assertOk()
            ->assertSee('Audit Detail')
            ->assertSee('AUD-')
            ->assertSee('Performed By')
            ->assertSee('Date &amp; Time', false)
            ->assertSee('Module')
            ->assertSee('Result')
            ->assertSee('IP Address')
            ->assertSee('CHANGES MADE')
            ->assertSee('Previous Value')
            ->assertSee('pending')
            ->assertSee('approved');

        $this->get(route('admin.applicant-audit.status-history', [
            'applicant' => $applicant->id,
            'application_id' => $application->id,
        ]))
            ->assertOk()
            ->assertSee('Previous Status')
            ->assertSee('Changed By');

    }

    public function test_audit_csv_escapes_formula_values_and_records_export_activity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'user', 'name' => '=2+2']);
        $application = Application::factory()->create([
            'user_id' => $applicant->id,
            'status' => 'pending',
        ]);
        app(AuditLogger::class)->record(
            AuditAction::APPLICATION_SUBMITTED,
            'Application',
            $applicant,
            $applicant,
            $application,
            ['field_name' => 'status', 'old_value' => null, 'new_value' => 'pending'],
        );

        $response = $this->actingAs($admin)
            ->get(route('admin.applicant-audit.export'));

        $response->assertOk();
        $this->assertStringContainsString("'=2+2", $response->streamedContent());
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::APPLICANT_REPORT_GENERATED,
            'actor_name' => $admin->name,
        ]);
    }

    public function test_approving_an_application_records_the_real_status_transition(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'user']);
        $application = Application::factory()->create([
            'user_id' => $applicant->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.applications.approve', $application))
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'applicant_id' => $applicant->id,
            'application_id' => $application->id,
            'user_id' => $admin->id,
            'action' => AuditAction::APPLICATION_APPROVED,
            'field_name' => 'status',
            'old_value' => 'pending',
            'new_value' => 'approved',
        ]);
    }

    public function test_rejecting_an_application_records_the_denied_transition_for_the_rejected_badge(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'user']);
        $application = Application::factory()->create([
            'user_id' => $applicant->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.applications.deny', $application))
            ->assertRedirect();

        $event = AuditLog::query()
            ->where('application_id', $application->id)
            ->where('field_name', 'status')
            ->firstOrFail();
        $this->assertSame(AuditAction::APPLICATION_REJECTED, $event->action);
        $this->assertSame('pending', $event->old_value);
        $this->assertSame('denied', $event->new_value);

        $this->get(route('admin.applicant-audit.index'))
            ->assertOk()
            ->assertSee('Rejected');
    }

    public function test_successful_and_failed_logins_write_security_audit_events(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'audit-admin@example.test',
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditAction::LOGIN_SUCCESS,
            'result' => 'successful',
        ]);

        auth()->logout();
        $this->from(route('login'))->post(route('login'), [
            'email' => $admin->email,
            'password' => 'incorrect',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::LOGIN_FAILED,
            'actor_name' => 'Unverified login attempt',
            'result' => 'failed',
        ]);
    }

    public function test_applicant_audit_pagination_preserves_search_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $selectedApplicant = null;
        for ($index = 0; $index < 11; $index++) {
            $applicant = User::factory()->create([
                'role' => 'user',
                'name' => 'Audit Applicant '.$index,
            ]);
            app(AuditLogger::class)->record(
                AuditAction::ACCOUNT_CREATED,
                'Authentication',
                $applicant,
                $applicant,
            );
            if ($index === 10) {
                $selectedApplicant = $applicant;
            }
        }

        $response = $this->actingAs($admin)
            ->get(route('admin.applicant-audit.index', ['search' => 'Audit Applicant', 'show_all_audit_logs' => 1]))
            ->assertOk()
            ->assertSee('Showing 1–10 of 11 applicants')
            ->assertSee('search=Audit%20Applicant', false);

        $this->assertNotNull($selectedApplicant);
        $this->assertSame(10, substr_count($response->getContent(), 'class="audit-applicant"'));
        $this->get(route('admin.applicant-audit.index', [
            'search' => 'Audit Applicant',
            'show_all_audit_logs' => 1,
            'page' => 2,
        ]))
            ->assertOk()
            ->assertSee($selectedApplicant->name)
            ->assertSee('class="audit-applicant"', false);
    }

    public function test_query_failure_renders_an_error_state_instead_of_zero_summaries(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $query = Mockery::mock(ApplicantAuditQuery::class);
        $query->shouldReceive('filterOptions')->once()->andThrow(new RuntimeException('Database unavailable'));
        $this->app->instance(ApplicantAuditQuery::class, $query);

        $this->actingAs($admin)
            ->get(route('admin.applicant-audit.index'))
            ->assertOk()
            ->assertSee('Applicant audit data could not be loaded.')
            ->assertDontSee('Total Applicants Audited');
    }

    public function test_export_applies_search_filter_to_streamed_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (['Included Applicant', 'Excluded Applicant'] as $name) {
            $applicant = User::factory()->create(['role' => 'user', 'name' => $name]);
            app(AuditLogger::class)->record(
                AuditAction::ACCOUNT_CREATED,
                'Authentication',
                $applicant,
                $applicant,
            );
        }

        $response = $this->actingAs($admin)
            ->get(route('admin.applicant-audit.export', ['search' => 'Included Applicant']))
            ->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Included Applicant', $csv);
        $this->assertStringNotContainsString('Excluded Applicant', $csv);
    }
}
