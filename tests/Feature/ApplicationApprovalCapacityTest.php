<?php

namespace Tests\Feature;

use App\Models\AdditionalRequirement;
use App\Models\Application;
use App\Models\AuditAction;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationApprovalCapacityTest extends TestCase
{
    use RefreshDatabase;

    public function test_filling_the_approval_limit_closes_submissions_and_notifies_pending_applicants(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $selectedApplicant = User::factory()->create();
        $pendingApplicant = User::factory()->create();
        $selectedApplication = Application::factory()->for($selectedApplicant)->create(['status' => 'pending']);
        $pendingApplication = Application::factory()->for($pendingApplicant)->create(['status' => 'pending']);
        SystemSetting::current()->update(['approved_applicant_limit' => 1]);

        $this->actingAs($admin)
            ->post(route('admin.applications.approve', $selectedApplication))
            ->assertRedirect()
            ->assertSessionHas('approval_limit_notice');

        $this->assertSame('approved', $selectedApplication->fresh()->status);
        $this->assertSame('denied', $pendingApplication->fresh()->status);
        $this->assertFalse(SystemSetting::current()->isApplicationOpen());
        $this->assertDatabaseHas('audit_logs', [
            'application_id' => $pendingApplication->id,
            'action' => AuditAction::APPLICATION_REJECTED,
            'field_name' => 'status',
            'old_value' => 'pending',
            'new_value' => 'denied',
        ]);

        $notification = $pendingApplicant->notifications()->firstOrFail();
        $this->assertSame('SPES application period closed', $notification->data['title']);
        $this->assertSame('approval_limit_reached', $notification->data['event']);
        $this->assertStringContainsString('try again next SPES season', $notification->data['message']);

        $this->actingAs($pendingApplicant)
            ->get(route('applications.edit'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('approval_capacity_closed', true);

        $newApplicant = User::factory()->create();
        $this->actingAs($newApplicant)
            ->get(route('applications.create'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('approval_capacity_closed', true);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('SPES applications are closed')
            ->assertSee('try again next SPES season');
    }

    public function test_full_capacity_blocks_nonapproved_requirement_uploads_but_not_approved_applicants(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $approvedApplicant = User::factory()->create();
        $pendingApplicant = User::factory()->create();
        Application::factory()->for($approvedApplicant)->create(['status' => 'approved']);
        $pendingApplication = Application::factory()->for($pendingApplicant)->create(['status' => 'pending']);
        $requirement = AdditionalRequirement::create([
            'name' => 'Proof of enrollment',
            'is_required' => true,
            'is_active' => true,
            'audience' => 'all_applicants',
        ]);
        SystemSetting::current()->update(['approved_applicant_limit' => 1]);

        $this->actingAs($pendingApplicant)
            ->get(route('applicant.requirements'))
            ->assertOk()
            ->assertSee('SPES applications are closed')
            ->assertSee('Additional requirement uploads are closed for this season');

        $this->post(route('applicant.requirements.upload', $requirement), [
            'documents' => [1 => UploadedFile::fake()->create('proof.pdf', 20, 'application/pdf')],
        ])->assertRedirect(route('applicant.requirements'));

        $this->assertDatabaseMissing('application_additional_requirements', [
            'application_id' => $pendingApplication->id,
            'additional_requirement_id' => $requirement->id,
        ]);

        $approvedApplication = $approvedApplicant->applications()->firstOrFail();
        $this->actingAs($approvedApplicant)
            ->post(route('applicant.requirements.upload', $requirement), [
                'documents' => [1 => UploadedFile::fake()->create('approved-proof.pdf', 20, 'application/pdf')],
            ])
            ->assertRedirect(route('applicant.requirements'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('application_additional_requirements', [
            'application_id' => $approvedApplication->id,
            'additional_requirement_id' => $requirement->id,
            'original_name' => 'approved-proof.pdf',
        ]);
    }

    public function test_admin_can_configure_approval_limit_and_see_live_capacity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $approved = User::factory()->create();
        Application::factory()->for($approved)->create(['status' => 'approved']);
        $pending = User::factory()->create();
        $pendingApplication = Application::factory()->for($pending)->create(['status' => 'pending']);

        $this->actingAs($admin)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Maximum approved applicants')
            ->assertSee('Approved this season: 1')
            ->assertSee('approved_applicant_limit');

        $this->put(route('admin.settings.update'), [
            'application_start_date' => null,
            'application_end_date' => null,
            'approved_applicant_limit' => 1,
        ])->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('approval_limit_notice');

        $this->assertSame(1, SystemSetting::current()->approved_applicant_limit);
        $this->assertSame('denied', $pendingApplication->fresh()->status);
        $this->assertSame(
            'approval_limit_reached',
            $pending->notifications()->firstOrFail()->data['event'],
        );
    }
}
