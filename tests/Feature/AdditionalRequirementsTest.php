<?php

namespace Tests\Feature;

use App\Models\AdditionalRequirement;
use App\Models\Application;
use App\Models\ApplicationAdditionalRequirement;
use App\Models\User;
use Database\Seeders\SpesRequirementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdditionalRequirementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_and_manage_additional_applicant_requirements(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.additional-requirements.index'))
            ->assertOk()
            ->assertSee('Decide What Applicants Must Submit')
            ->assertSee('Applications')
            ->assertSee('aria-expanded="true"', false)
            ->assertSee(route('admin.applications.index'), false)
            ->assertSee(route('admin.additional-requirements.index'), false)
            ->assertSee(route('admin.masterlist.index'), false)
            ->assertSee('Downloadable files')
            ->assertSee('webkitdirectory', false)
            ->assertSee('Submission deadline')
            ->assertSee('Requirement title')
            ->assertSee('Applicant access')
            ->assertSee('Approved applicants only')
            ->assertSee('data-requirement-edit', false);

        $this->post(route('admin.additional-requirements.store'), [
            'name' => 'Parent Consent Form',
            'description' => 'Upload a signed copy.',
            'is_required' => '1',
            'is_active' => '1',
            'audience' => 'approved_applicants',
        ])->assertRedirect(route('admin.additional-requirements.index'));

        $requirement = AdditionalRequirement::firstOrFail();
        $this->assertTrue($requirement->is_required);
        $this->assertTrue($requirement->is_active);
        $this->get(route('admin.additional-requirements.index'))
            ->assertOk()
            ->assertSee('data-unsaved-cancel="requirement-update-'.$requirement->id.'"', false)
            ->assertSee('data-admin-confirm-backdrop', false);

        $this->put(route('admin.additional-requirements.update', $requirement), [
            'name' => 'Parent Consent Form',
            'description' => 'Upload a signed copy.',
            'is_required' => '0',
            'is_active' => '0',
            'audience' => 'all_applicants',
        ])->assertRedirect(route('admin.additional-requirements.index'));

        $this->assertFalse($requirement->fresh()->is_required);
        $this->assertFalse($requirement->fresh()->is_active);
    }

    public function test_requirement_can_have_multiple_downloadable_files_for_applicants(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.additional-requirements.store'), [
                'name' => 'Application packet',
                'description' => 'Download and complete all files in this packet.',
                'is_required' => '1',
                'is_active' => '1',
                'audience' => 'all_applicants',
                'templates' => [
                    UploadedFile::fake()->create('application.pdf', 20, 'application/pdf'),
                    UploadedFile::fake()->create('instructions.docx', 20, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
                ],
            ])
            ->assertRedirect(route('admin.additional-requirements.index'));

        $requirement = AdditionalRequirement::where('name', 'Application packet')->firstOrFail();
        $templates = $requirement->templates;
        $this->assertCount(2, $templates);

        $applicant = User::factory()->create();
        Application::factory()->for($applicant)->create(['status' => 'approved']);
        $this->actingAs($applicant)
            ->get(route('applicant.requirements'))
            ->assertOk()
            ->assertSee('application.pdf')
            ->assertSee('instructions.docx');

        foreach ($templates as $template) {
            $this->get(route('applicant.requirements.template-file', [
                'additionalRequirement' => $requirement,
                'template' => $template,
            ]))
                ->assertOk()
                ->assertDownload($template->original_name);
        }
    }

    public function test_admin_can_add_and_remove_individual_files_when_editing_a_requirement(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $requirement = AdditionalRequirement::create([
            'name' => 'Application packet',
            'is_required' => true,
            'is_active' => true,
            'audience' => 'all_applicants',
        ]);
        $oldPath = 'requirements/templates/old-guide.pdf';
        Storage::disk('local')->put($oldPath, 'old guide');
        $oldTemplate = $requirement->templates()->create([
            'file_path' => $oldPath,
            'original_name' => 'old-guide.pdf',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.additional-requirements.update', $requirement), [
                'name' => 'Application packet',
                'description' => 'New instructions.',
                'is_required' => '1',
                'is_active' => '1',
                'audience' => 'all_applicants',
                'remove_template_files' => [$oldTemplate->id],
                'templates' => [
                    UploadedFile::fake()->create('new-guide.pdf', 20, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('admin.additional-requirements.index'));

        Storage::disk('local')->assertMissing($oldPath);
        $this->assertDatabaseMissing('additional_requirement_templates', ['id' => $oldTemplate->id]);
        $this->assertDatabaseHas('additional_requirement_templates', [
            'additional_requirement_id' => $requirement->id,
            'original_name' => 'new-guide.pdf',
        ]);
    }

    public function test_admin_can_publish_a_downloadable_form_with_a_submission_deadline(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $dueAt = now()->addDays(5)->startOfMinute();

        $this->actingAs($admin)
            ->post(route('admin.additional-requirements.store'), [
                'name' => 'SPES Application Form',
                'description' => 'Complete and sign the form.',
                'is_required' => '1',
                'is_active' => '1',
                'audience' => 'approved_applicants',
                'due_at' => $dueAt->format('Y-m-d\TH:i'),
                'template' => UploadedFile::fake()->create('spes-form.pdf', 40, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.additional-requirements.index'));

        $requirement = AdditionalRequirement::where('name', 'SPES Application Form')->firstOrFail();
        $this->assertTrue($requirement->is_required);
        $this->assertEquals($dueAt->timestamp, $requirement->due_at->timestamp);
        $this->assertSame('spes-form.pdf', $requirement->template_original_name);
        Storage::disk('local')->assertExists($requirement->template_path);

        $this->put(route('admin.additional-requirements.update', $requirement), [
            'name' => 'SPES Application Form',
            'description' => 'Complete and sign the form.',
            'is_required' => '1',
            'is_active' => '1',
            'audience' => 'approved_applicants',
            'due_at' => '',
            'remove_template' => '1',
        ])->assertRedirect(route('admin.additional-requirements.index'));

        $this->assertNull($requirement->fresh()->due_at);
        $this->assertNull($requirement->fresh()->template_path);
        Storage::disk('local')->assertMissing($requirement->template_path);
    }

    public function test_admin_can_edit_requirement_content_and_replace_its_downloadable_file(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $oldTemplatePath = 'requirements/templates/old-form.pdf';
        Storage::disk('local')->put($oldTemplatePath, 'old form contents');
        $requirement = AdditionalRequirement::create([
            'name' => 'Old Requirement Name',
            'description' => 'Old instructions.',
            'is_required' => true,
            'is_active' => true,
            'audience' => 'approved_applicants',
            'template_path' => $oldTemplatePath,
            'template_original_name' => 'old-form.pdf',
        ]);
        $dueAt = now()->addDays(7)->startOfMinute();

        $this->actingAs($admin)
            ->put(route('admin.additional-requirements.update', $requirement), [
                'name' => 'Updated Requirement Name',
                'description' => 'Updated instructions.',
                'is_required' => '0',
                'is_active' => '1',
                'audience' => 'all_applicants',
                'due_at' => $dueAt->format('Y-m-d\TH:i'),
                'template' => UploadedFile::fake()->create('updated-form.pdf', 50, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.additional-requirements.index'));

        $requirement->refresh();
        $this->assertSame('Updated Requirement Name', $requirement->name);
        $this->assertSame('Updated instructions.', $requirement->description);
        $this->assertFalse($requirement->is_required);
        $this->assertSame('all_applicants', $requirement->audience);
        $this->assertEquals($dueAt->timestamp, $requirement->due_at->timestamp);
        $this->assertSame('updated-form.pdf', $requirement->template_original_name);
        Storage::disk('local')->assertMissing($oldTemplatePath);
        Storage::disk('local')->assertExists($requirement->template_path);
    }

    public function test_provided_spes_forms_are_seeded_as_downloadable_requirements(): void
    {
        Storage::fake('local');

        $this->seed(SpesRequirementSeeder::class);

        $this->assertDatabaseCount('additional_requirements', 4);
        $requirements = AdditionalRequirement::all();
        foreach ($requirements as $requirement) {
            $this->assertTrue($requirement->is_required);
            $this->assertTrue($requirement->is_active);
            Storage::disk('local')->assertExists($requirement->template_path);
        }

        $applicant = User::factory()->create();
        Application::factory()->for($applicant)->create(['status' => 'approved']);
        $this->actingAs($applicant);
        foreach ($requirements as $requirement) {
            $this->get(route('applicant.requirements.template', $requirement))
                ->assertOk()
                ->assertDownload($requirement->template_original_name);
        }

        $this->seed(SpesRequirementSeeder::class);
        $this->assertDatabaseCount('additional_requirements', 4);
    }

    public function test_applicant_can_upload_and_view_an_admin_added_requirement(): void
    {
        Storage::fake('local');
        $applicant = User::factory()->create();
        $application = Application::factory()->for($applicant)->create();
        $requirement = AdditionalRequirement::create([
            'name' => 'Parent Consent Form',
            'description' => 'Upload a signed copy.',
            'is_required' => true,
            'is_active' => true,
            'audience' => 'all_applicants',
        ]);

        $this->actingAs($applicant)
            ->get(route('applicant.requirements'))
            ->assertOk()
            ->assertSee('Parent Consent Form')
            ->assertSee('Required')
            ->assertSee('Upload a signed copy.')
            ->assertSee('Submit as soon as possible')
            ->assertSee('data-sidebar-close', false)
            ->assertSee('data-sidebar-backdrop', false)
            ->assertDontSee('Certificate of Enrollment')
            ->assertDontSee('Certificate of Grades');

        $this->post(route('applicant.requirements.upload', $requirement), [
            'documents' => [1 => UploadedFile::fake()->image('consent.png')],
        ])->assertRedirect(route('applicant.requirements'));

        $this->assertDatabaseHas('application_additional_requirements', [
            'application_id' => $application->id,
            'additional_requirement_id' => $requirement->id,
            'original_name' => 'consent.png',
        ]);

        $submission = $application->additionalRequirementSubmissions()->firstOrFail();
        Storage::disk('local')->assertExists($submission->file_path);

        $this->get(route('applicant.requirements'))
            ->assertOk()
            ->assertSee('Submitted')
            ->assertSee('consent.png');

        $this->get(route('applications.additional-requirements.document', [
            'application' => $application,
            'additionalRequirement' => $requirement,
        ]))->assertOk();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('admin.applications.show', $application))
            ->assertOk()
            ->assertSee('Parent Consent Form');

        $this->get(route('applications.additional-requirements.document', [
            'application' => $application,
            'additionalRequirement' => $requirement,
        ]))->assertOk();
    }

    public function test_applicant_submits_one_file_for_each_requirement_template_and_can_view_each_separately(): void
    {
        Storage::fake('local');
        $applicant = User::factory()->create();
        $application = Application::factory()->for($applicant)->create();
        $requirement = AdditionalRequirement::create([
        'name' => 'School Documents',
        'description' => 'Submit each requested document.',
        'is_required' => true,
        'is_active' => true,
        'audience' => 'all_applicants',
        ]);
        $templateOne = $requirement->templates()->create([
        'file_path' => 'requirements/templates/school-id.pdf',
        'original_name' => 'School ID template.pdf',
        ]);
        $templateTwo = $requirement->templates()->create([
        'file_path' => 'requirements/templates/certificate.pdf',
        'original_name' => 'Certificate template.pdf',
        ]);

        $this->actingAs($applicant)
        ->get(route('applicant.requirements'))
        ->assertOk()
        ->assertSee('File 1 — School ID template.pdf')
        ->assertSee('File 2 — Certificate template.pdf')
        ->assertSee('Submit 2 files');

        $this->post(route('applicant.requirements.upload', $requirement), [
        'documents' => [
            1 => UploadedFile::fake()->create('school-id.pdf', 20, 'application/pdf'),
            2 => UploadedFile::fake()->image('certificate.png'),
        ],
        ])->assertRedirect(route('applicant.requirements'));

        $submissions = $application->additionalRequirementSubmissions()->orderBy('file_number')->get();
        $this->assertCount(2, $submissions);
        $this->assertSame([1, 2], $submissions->pluck('file_number')->all());
        $this->assertSame(['school-id.pdf', 'certificate.png'], $submissions->pluck('original_name')->all());
        foreach ($submissions as $submission) {
        Storage::disk('local')->assertExists($submission->file_path);
        }

        $this->get(route('applicant.requirements'))
        ->assertOk()
        ->assertSee('2 of 2 requested files submitted')
        ->assertSee('View file 1: school-id.pdf')
        ->assertSee('View file 2: certificate.png');

        foreach ($submissions as $index => $submission) {
        $filename = $index === 0 ? 'school-id.pdf' : 'certificate.png';
        $this->get(route('applications.additional-requirements.submission', [
            'application' => $application,
            'additionalRequirement' => $requirement,
            'submission' => $submission,
        ]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="'.$filename.'"');
        }

        $otherRequirement = AdditionalRequirement::create([
        'name' => 'Unrelated Requirement',
        'is_required' => true,
        'is_active' => true,
        'audience' => 'all_applicants',
        ]);
        $this->get(route('applications.additional-requirements.submission', [
        'application' => $application,
        'additionalRequirement' => $otherRequirement,
        'submission' => $submissions->first(),
        ]))->assertNotFound();
    }

    public function test_approved_applicant_can_download_form_and_sees_the_next_steps_and_deadline(): void
    {
        Storage::fake('local');
        $applicant = User::factory()->create();
        Application::factory()->for($applicant)->create(['status' => 'approved']);
        $templatePath = 'requirements/templates/application-form.docx';
        Storage::disk('local')->put($templatePath, 'form contents');
        $dueAt = now()->addDays(3)->startOfMinute();
        $requirement = AdditionalRequirement::create([
            'name' => 'SPES Application Form',
            'description' => 'Complete and sign the form.',
            'is_required' => true,
            'is_active' => true,
            'template_path' => $templatePath,
            'template_original_name' => 'SPES Application Form.docx',
            'due_at' => $dueAt,
        ]);

        $this->actingAs($applicant)
            ->get(route('applications.myApplication'))
            ->assertOk()
            ->assertSee('You are a proposed SPES applicant')
            ->assertSee('This is a preliminary step and is not yet final confirmation')
            ->assertSee(route('applicant.requirements'));

        $this->get(route('applicant.requirements'))
            ->assertOk()
            ->assertSee('Download each requested form')
            ->assertSee('SPES Application Form')
            ->assertSee('Due')
            ->assertSee($dueAt->format('F j, Y'))
            ->assertSee(route('applicant.requirements.template', $requirement));

        $this->get(route('applicant.requirements.template', $requirement))
            ->assertOk()
            ->assertDownload('SPES Application Form.docx');

        Storage::disk('local')->assertExists($templatePath);
    }

    public function test_applicant_must_be_approved_to_download_requirement_templates(): void
    {
        Storage::fake('local');
        $applicant = User::factory()->create();
        Application::factory()->for($applicant)->create(['status' => 'pending']);
        $templatePath = 'requirements/templates/pending-form.pdf';
        Storage::disk('local')->put($templatePath, 'form contents');
        $requirement = AdditionalRequirement::create([
            'name' => 'Application Form',
            'is_required' => true,
            'is_active' => true,
            'template_path' => $templatePath,
            'template_original_name' => 'application.pdf',
        ]);

        $this->actingAs($applicant)
            ->get(route('applicant.requirements'))
            ->assertOk()
            ->assertDontSee('Application Form')
            ->assertSee('Second-phase requirements will be available here after your application is approved.')
            ->assertDontSee(route('applicant.requirements.template', $requirement));

        $this->get(route('applicant.requirements.template', $requirement))
            ->assertForbidden();
    }

    public function test_applicants_cannot_upload_for_another_applicant_or_access_admin_management(): void
    {
        $owner = User::factory()->create();
        $application = Application::factory()->for($owner)->create();
        $requirement = AdditionalRequirement::create([
            'name' => 'Proof of Address',
            'is_required' => true,
            'is_active' => true,
            'audience' => 'all_applicants',
        ]);
        $otherApplicant = User::factory()->create();

        $this->actingAs($otherApplicant)
            ->post(route('applicant.requirements.upload', $requirement), [
                'documents' => [1 => UploadedFile::fake()->image('proof.png')],
            ])
            ->assertNotFound();

        $this->get(route('applications.additional-requirements.document', [
            'application' => $application,
            'additionalRequirement' => $requirement,
        ]))->assertForbidden();

        $this->get(route('admin.additional-requirements.index'))
            ->assertForbidden();
    }

    public function test_inactive_requirements_are_hidden_until_the_applicant_has_submitted_them(): void
    {
        $applicant = User::factory()->create();
        $application = Application::factory()->for($applicant)->create();
        $requirement = AdditionalRequirement::create([
            'name' => 'Archived Requirement',
            'is_required' => false,
            'is_active' => false,
            'audience' => 'all_applicants',
        ]);

        $this->actingAs($applicant)
            ->get(route('applicant.requirements'))
            ->assertOk()
            ->assertDontSee('Archived Requirement');

        ApplicationAdditionalRequirement::create([
            'application_id' => $application->id,
            'additional_requirement_id' => $requirement->id,
            'file_path' => 'requirements/archived.png',
            'original_name' => 'archived.png',
        ]);

        $this->get(route('applicant.requirements'))
            ->assertOk()
            ->assertSee('Archived Requirement')
            ->assertSee('Submitted');
    }

    public function test_approved_only_requirements_are_hidden_and_blocked_until_application_is_approved(): void
    {
        Storage::fake('local');
        $applicant = User::factory()->create();
        $application = Application::factory()->for($applicant)->create(['status' => 'pending']);
        $templatePath = 'requirements/templates/second-phase.pdf';
        Storage::disk('local')->put($templatePath, 'form contents');
        $requirement = AdditionalRequirement::create([
            'name' => 'Second Phase Form',
            'is_required' => true,
            'is_active' => true,
            'audience' => 'approved_applicants',
            'template_path' => $templatePath,
            'template_original_name' => 'second-phase.pdf',
        ]);

        $this->actingAs($applicant)
            ->get(route('applicant.requirements'))
            ->assertOk()
            ->assertDontSee('Second Phase Form')
            ->assertSee('Second-phase requirements will be available here after your application is approved.');

        $this->post(route('applicant.requirements.upload', $requirement), [
            'documents' => [1 => UploadedFile::fake()->image('second-phase.png')],
        ])->assertForbidden();
        $this->get(route('applicant.requirements.template', $requirement))->assertForbidden();
        $this->assertDatabaseMissing('application_additional_requirements', [
            'application_id' => $application->id,
            'additional_requirement_id' => $requirement->id,
        ]);

        $application->update(['status' => 'approved']);
        $this->get(route('applicant.requirements'))
            ->assertOk()
            ->assertSee('Second Phase Form')
            ->assertSee(route('applicant.requirements.template', $requirement));

        $this->post(route('applicant.requirements.upload', $requirement), [
            'documents' => [1 => UploadedFile::fake()->image('second-phase.png')],
        ])->assertRedirect(route('applicant.requirements'));

        $application->update(['status' => 'pending']);
        $this->get(route('applications.additional-requirements.document', [
            'application' => $application,
            'additionalRequirement' => $requirement,
        ]))->assertForbidden();
    }
}
