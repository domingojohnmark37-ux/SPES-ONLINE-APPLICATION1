<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use App\Notifications\ApplicantPortalUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_submit_application()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('applications.store'), $this->validApplicationPayload());

        $response->assertRedirect(route('applications.myApplication'));
        $this->assertDatabaseHas('applications', [
            'user_id' => $user->id,
            'full_name' => 'Juan Dela Cruz',
            'parent_status' => 'Both Parents Living',
            'grade_year_level' => '2nd year',
            'status' => 'pending',
        ]);
        $application = Application::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('2nd year', $application->grade_year_level);
        $this->assertSame('birth-certificate.pdf', $application->document_original_names['resume']);
        $this->assertSame('certificate-of-enrollment.pdf', $application->document_original_names['certificate_enrollment']);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => ApplicantPortalUpdate::class,
        ]);
        $this->assertDatabaseHas('applicant_notification_deliveries', [
            'user_id' => $user->id,
            'event_hash' => hash('sha256', "application:{$application->id}:submitted:{$application->created_at->getTimestamp()}"),
            'channel' => 'mail',
            'status' => 'sent',
        ]);
    }

    /** @test */
    public function post_approval_form_routes_and_fields_are_removed()
    {
        $this->assertFalse(Route::has('applications.form2'));
        $this->assertFalse(Route::has('applications.form2.store'));
        $this->assertFalse(Route::has('admin.applications.forms'));
        $this->assertFalse(Schema::hasColumn('applications', 'forms_step'));
        $this->assertFalse(Schema::hasColumn('applications', 'f2_consent_accepted'));
    }

    /** @test */
    public function application_form_asks_for_applicant_education_before_parent_status()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('applications.create'))
            ->assertOk()
            ->assertSeeInOrder([
                'Your Educational Attainment',
                'name="education"',
                'Grade/Year Level',
                'name="grade_year_level"',
                'Grade 7',
                'Grade 8',
                'Grade 9',
                'Grade 10',
                'Grade 11',
                'Grade 12',
                '1st year',
                '2nd year',
                '4th year',
                '5th year',
                'Parent Status',
                'name="parent_status"',
            ], false);
    }

    /** @test */
    public function application_requires_birth_and_enrollment_certificates()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('applications.store'), $this->validApplicationPayload([
            'resume' => null,
            'certificate_enrollment' => null,
        ]));

        $response->assertSessionHasErrors(['resume', 'certificate_enrollment']);
        $this->assertDatabaseMissing('applications', ['user_id' => $user->id]);
    }

    /** @test */
    public function application_rejects_a_standalone_middle_name_initial()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('applications.store'), $this->validApplicationPayload([
            'middle_name' => 'A.',
        ]));

        $response->assertSessionHasErrors('middle_name');
        $this->assertDatabaseMissing('applications', ['user_id' => $user->id]);
    }

    /** @test */
    public function user_can_reapply_after_application_is_denied()
    {
        $user = User::factory()->create();
        $application = Application::factory()->for($user)->create([
            'status' => 'denied',
            'admin_comment' => 'Please update your information.',
        ]);

        $this->actingAs($user);

        $response = $this->put(route('applications.update'), $this->validApplicationPayload([
            'full_name' => 'Juan Updated',
        ]));

        $response->assertRedirect(route('applications.myApplication'));

        $application->refresh();
        $this->assertSame('pending', $application->status);
        $this->assertNull($application->admin_comment);
        $this->assertSame('Juan Updated', $application->full_name);
    }

    public function test_reapplication_keeps_submitted_documents_unless_replaced(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $birthCertificate = 'applications/resumes/birth-certificate-old.pdf';
        $enrollmentCertificate = 'applications/enrollment/enrollment-old.pdf';
        $gradeCertificate = 'applications/grades/grades.pdf';
        Storage::disk('public')->put($birthCertificate, 'original birth certificate');
        Storage::disk('public')->put($enrollmentCertificate, 'original enrollment certificate');
        Storage::disk('public')->put($gradeCertificate, 'original grades');
        $application = Application::factory()->for($user)->create([
            'status' => 'denied',
            'resume' => $birthCertificate,
            'certificate_enrollment' => $enrollmentCertificate,
            'certificate_grade' => $gradeCertificate,
            'mother_name' => 'Existing Mother Name',
            'document_original_names' => [
                'resume' => 'birth-certificate-old.pdf',
                'certificate_enrollment' => 'enrollment-old.pdf',
                'certificate_grade' => 'grades.pdf',
            ],
        ]);

        $this->actingAs($user)
            ->get(route('applications.edit'))
            ->assertOk()
            ->assertSee('Your previously submitted information and documents will stay on your application.')
            ->assertSee('birth-certificate-old.pdf')
            ->assertSee('enrollment-old.pdf')
            ->assertSee('Choose a replacement PDF (optional)');

        $formResponse = $this->get(route('applications.edit'));
        preg_match('/<input[^>]*name="resume"[^>]*>/', $formResponse->getContent(), $resumeInput);
        preg_match('/<input[^>]*name="certificate_enrollment"[^>]*>/', $formResponse->getContent(), $enrollmentInput);
        $this->assertNotEmpty($resumeInput);
        $this->assertNotEmpty($enrollmentInput);
        $this->assertStringNotContainsString('required', $resumeInput[0]);
        $this->assertStringNotContainsString('required', $enrollmentInput[0]);

        $this->put(route('applications.update'), $this->validApplicationPayload([
            'resume' => null,
            'certificate_enrollment' => null,
            'mother_name' => 'Existing Mother Name',
        ]))->assertRedirect(route('applications.myApplication'));

        $application->refresh();
        $this->assertSame('pending', $application->status);
        $this->assertSame($birthCertificate, $application->resume);
        $this->assertSame($enrollmentCertificate, $application->certificate_enrollment);
        $this->assertSame($gradeCertificate, $application->certificate_grade);
        $this->assertSame('Existing Mother Name', $application->mother_name);
        $this->assertSame('birth-certificate-old.pdf', $application->document_original_names['resume']);
        Storage::disk('public')->assertExists($birthCertificate);
        Storage::disk('public')->assertExists($enrollmentCertificate);
        Storage::disk('public')->assertExists($gradeCertificate);
    }

    public function test_applicant_can_replace_a_previously_submitted_document_when_reapplying(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $birthCertificate = 'applications/resumes/birth-certificate-old.pdf';
        $enrollmentCertificate = 'applications/enrollment/enrollment-old.pdf';
        Storage::disk('public')->put($birthCertificate, 'original birth certificate');
        Storage::disk('public')->put($enrollmentCertificate, 'original enrollment certificate');
        $application = Application::factory()->for($user)->create([
            'status' => 'denied',
            'resume' => $birthCertificate,
            'certificate_enrollment' => $enrollmentCertificate,
            'document_original_names' => [
                'resume' => 'birth-certificate-old.pdf',
                'certificate_enrollment' => 'enrollment-old.pdf',
            ],
        ]);

        $this->actingAs($user)
            ->put(route('applications.update'), $this->validApplicationPayload([
                'resume' => UploadedFile::fake()->create('birth-certificate-replacement.pdf', 100, 'application/pdf'),
                'certificate_enrollment' => null,
            ]))
            ->assertRedirect(route('applications.myApplication'));

        $application->refresh();
        $this->assertSame('pending', $application->status);
        $this->assertNotSame($birthCertificate, $application->resume);
        $this->assertSame('birth-certificate-replacement.pdf', $application->document_original_names['resume']);
        Storage::disk('public')->assertMissing($birthCertificate);
        Storage::disk('public')->assertExists($application->resume);
        Storage::disk('public')->assertExists($enrollmentCertificate);
        $this->assertSame($enrollmentCertificate, $application->certificate_enrollment);
    }

    public function test_reapplication_still_requires_documents_that_are_not_on_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Application::factory()->for($user)->create([
            'status' => 'denied',
            'resume' => null,
            'certificate_enrollment' => null,
        ]);

        $this->actingAs($user)
            ->put(route('applications.update'), $this->validApplicationPayload([
                'resume' => null,
                'certificate_enrollment' => null,
            ]))
            ->assertSessionHasErrors(['resume', 'certificate_enrollment']);
    }

    /** @test */
    public function user_sees_upload_button_when_birth_certificate_is_missing()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Application::factory()->for($user)->create(['resume' => null]);

        $this->get(route('applications.myApplication'))
            ->assertOk()
            ->assertSee('Upload');
    }

    /** @test */
    public function user_sees_view_button_when_birth_certificate_exists()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Application::factory()->for($user)->create([
            'resume' => 'applications/resumes/sample.pdf',
            'status' => 'pending',
        ]);

        $this->get(route('applications.myApplication'))
            ->assertOk()
            ->assertSee('Birth Certificate')
            ->assertDontSee('Resume')
            ->assertSee('View');
    }

    /** @test */
    public function applicant_requirements_page_only_shows_documents_requested_by_admin()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Application::factory()->for($user)->create([
            'resume' => 'applications/resumes/sample.pdf',
            'certificate_enrollment' => 'applications/enrollment/sample.pdf',
        ]);

        $this->get(route('applicant.requirements'))
            ->assertOk()
            ->assertSee('View and manage your application requirements')
            ->assertSee('0 of 0 requested documents submitted')
            ->assertSee('No additional documents have been requested by PESO.')
            ->assertDontSee('Certificate of Enrollment')
            ->assertDontSee('Certificate of Grades')
            ->assertDontSee('Certificate of Indigency')
            ->assertSee(route('applicant.requirements'));
    }

    /** @test */
    public function requirements_page_is_only_available_to_applicants()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('applicant.requirements'))
            ->assertForbidden();
    }

    public function test_applicant_page_hides_status_but_keeps_denied_application_feedback_and_reapply(): void
    {
        $user = User::factory()->create();
        Application::factory()->for($user)->create([
            'status' => 'denied',
            'admin_comment' => 'Please provide a clearer certificate.',
        ]);

        $this->actingAs($user)
            ->get(route('applications.myApplication'))
            ->assertOk()
            ->assertDontSee('Current Status')
            ->assertDontSee('Your application was Denied')
            ->assertSee('Application Status')
            ->assertSee('Please provide a clearer certificate.')
            ->assertSee('Reapply');
    }

    public function test_approved_applicant_sees_the_proposed_applicant_next_steps(): void
    {
        $user = User::factory()->create();
        Application::factory()->for($user)->create(['status' => 'approved']);

        $this->actingAs($user)
            ->get(route('applications.myApplication'))
            ->assertOk()
            ->assertSee('You are a proposed SPES applicant')
            ->assertSee('not yet final confirmation of program participation')
            ->assertSee('complete and sign them as instructed')
            ->assertSee('as soon as possible')
            ->assertSee(route('applicant.requirements'));
    }

    public function test_applicant_navigation_is_shared_and_legacy_updates_redirect_to_notifications(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('applications.myApplication'))
            ->assertOk()
            ->assertDontSee('Current Status')
            ->assertSee('Application Status')
            ->assertSee('FAQs')
            ->assertSee('Need Help &amp; Support', false)
            ->assertSee('id="applicant-support-submenu"', false)
            ->assertSee('Contact Us')
            ->assertSee('data-open-portal-help', false);

        $this->get(route('applications.create'))
            ->assertOk()
            ->assertSee('Application Status')
            ->assertSee('FAQs')
            ->assertSee('Need Help &amp; Support', false)
            ->assertSee('id="applicant-support-submenu"', false)
            ->assertSee('Contact Us')
            ->assertSee('data-open-portal-help', false);

        Application::factory()->for($user)->create(['status' => 'approved']);

        $this->get(route('updates'))
            ->assertRedirect(route('applicant.notifications.recent'));

        $this->get(route('applicant.notifications.recent'))
            ->assertOk()
            ->assertSee('Application Status')
            ->assertDontSee('href="'.route('updates').'"', false)
            ->assertSee('FAQs')
            ->assertSee('data-open-portal-help', false);
    }

    /** @test */
    public function user_document_preview_uses_birth_certificate_name()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $path = 'applications/resumes/sample.pdf';
        $enrollmentPath = 'applications/enrollment/enrollment.pdf';
        Storage::disk('public')->put($path, 'sample pdf');
        Storage::disk('public')->put($enrollmentPath, 'sample enrollment pdf');

        $application = Application::factory()->for($user)->create([
            'resume' => $path,
            'certificate_enrollment' => $enrollmentPath,
            'status' => 'denied',
            'document_original_names' => [
                'resume' => 'birth-certificate.pdf',
                'certificate_enrollment' => 'enrollment-certificate.pdf',
            ],
        ]);

        $this->actingAs($user);

        $this->get(route('applications.myApplication'))
            ->assertOk()
            ->assertSee(route('applications.document.preview', ['application' => $application, 'document' => 'resume']), false)
            ->assertSee(route('applications.document.preview', ['application' => $application, 'document' => 'certificate_enrollment']), false)
            ->assertDontSee('openDocumentModal');

        $this->get(route('applications.document.preview', ['application' => $application, 'document' => 'resume']))
            ->assertOk()
            ->assertSee('Back to application')
            ->assertSee(route('applications.edit'), false)
            ->assertSee(route('applications.document.stream', ['application' => $application, 'document' => 'resume']), false)
            ->assertSee('Birth Certificate')
            ->assertSee('birth-certificate.pdf');

        $this->get(route('applications.document.preview', ['application' => $application, 'document' => 'certificate_enrollment']))
            ->assertOk()
            ->assertSee('Back to application')
            ->assertSee(route('applications.edit'), false)
            ->assertSee(route('applications.document.stream', ['application' => $application, 'document' => 'certificate_enrollment']), false)
            ->assertSee('Certificate of Enrollment')
            ->assertSee('enrollment-certificate.pdf');

        $this->get(route('applications.document.stream', ['application' => $application, 'document' => 'resume']))
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename=birth-certificate.pdf');

        $this->get(route('applications.document.stream', ['application' => $application, 'document' => 'certificate_enrollment']))
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename=enrollment-certificate.pdf');
    }

    /** @test */
    public function admin_sees_uploaded_birth_certificate_on_application_detail()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $application = Application::factory()->create([
            'resume' => 'applications/resumes/sample.pdf',
        ]);

        $this->actingAs($admin);

        $this->get(route('admin.applications.show', $application))
            ->assertOk()
            ->assertSee('Birth Certificate')
            ->assertSee('View')
            ->assertSee('Download');
    }

    /** @test */
    public function admin_can_approve_and_deny_application()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $application = Application::factory()->create();
        $applicant = $application->user;

        $this->actingAs($admin);
        $this->post(route('admin.applications.approve', $application))
            ->assertRedirect();
        $this->assertEquals('approved', $application->fresh()->status);

        $this->post(route('admin.applications.deny', $application))
            ->assertRedirect();
        $this->assertEquals('denied', $application->fresh()->status);
    }

    private function validApplicationPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Juan Dela Cruz',
            'sex' => 'Male',
            'birthday' => now()->subYears(20)->format('Y-m-d'),
            'age' => 20,
            'barangay' => 'Bical',
            'civil_status' => 'Single',
            'parent_status' => 'Both Parents Living',
            'education' => 'College (Currently Enrolled)',
            'grade_year_level' => '2nd year',
            'spes_status' => 'new',
            'mother_name' => 'Maria Dela Cruz',
            'mother_occupation' => 'Teacher',
            'mother_contact_no' => '09123456789',
            'father_guardian_name' => 'Pedro Dela Cruz',
            'father_occupation' => 'Driver',
            'father_contact_no' => '09987654321',
            'facebook' => 'https://facebook.com/juan',
            'messenger' => 'juan.dela.cruz',
            'resume' => UploadedFile::fake()->create('birth-certificate.pdf', 100, 'application/pdf'),
            'certificate_enrollment' => UploadedFile::fake()->create('certificate-of-enrollment.pdf', 100, 'application/pdf'),
        ], $overrides);
    }
}
