<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use App\Jobs\NotifyApplicantsJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_submit_application()
    {
        Storage::fake('local');
        Queue::fake();
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
        $this->assertStringStartsWith('applications/birth_certificates/', $application->birth_certificate);
        Storage::disk('local')->assertExists($application->birth_certificate);
        $this->assertSame('birth-certificate.pdf', $application->document_original_names['birth_certificate']);
        $this->assertSame('certificate-of-enrollment.pdf', $application->document_original_names['certificate_enrollment']);
        Queue::assertPushed(NotifyApplicantsJob::class, fn (NotifyApplicantsJob $job): bool =>
            $job->eventKey === "application:{$application->id}:submitted:{$application->created_at->getTimestamp()}"
            && $job->userIds === [$user->id]
            && $job->afterCommit
        );
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

    public function test_application_form_uses_birth_certificate_field_name_and_matching_labels(): void
    {
            $this->actingAs(User::factory()->create())
                ->get(route('applications.create'))
                ->assertOk()
                ->assertSee('for="birth_certificate"', false)
                ->assertSee('name="birth_certificate"', false)
                ->assertSee('id="birth_certificate"', false)
                ->assertSee('id="birthCertificateName"', false)
                ->assertSee('validateDocument(this, \'birthCertificateName\')', false)
                ->assertSee('Birth Certificate');
    }

    /** @test */
    public function application_requires_birth_and_enrollment_certificates()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('applications.store'), $this->validApplicationPayload([
            'birth_certificate' => null,
            'certificate_enrollment' => null,
        ]));

        $response->assertSessionHasErrors(['birth_certificate', 'certificate_enrollment']);
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
        Storage::fake('local');
        $user = User::factory()->create();
        $birthCertificate = 'applications/birth_certificates/birth-certificate-old.pdf';
        $enrollmentCertificate = 'applications/enrollment/enrollment-old.pdf';
        $gradeCertificate = 'applications/grades/grades.pdf';
        Storage::disk('local')->put($birthCertificate, 'original birth certificate');
        Storage::disk('local')->put($enrollmentCertificate, 'original enrollment certificate');
        Storage::disk('local')->put($gradeCertificate, 'original grades');
        $application = Application::factory()->for($user)->create([
            'status' => 'denied',
            'birth_certificate' => $birthCertificate,
            'certificate_enrollment' => $enrollmentCertificate,
            'certificate_grade' => $gradeCertificate,
            'mother_name' => 'Existing Mother Name',
            'document_original_names' => [
                'birth_certificate' => 'birth-certificate-old.pdf',
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
        preg_match('/<input[^>]*name="birth_certificate"[^>]*>/', $formResponse->getContent(), $birthCertificateInput);
        preg_match('/<input[^>]*name="certificate_enrollment"[^>]*>/', $formResponse->getContent(), $enrollmentInput);
        $this->assertNotEmpty($birthCertificateInput);
        $this->assertNotEmpty($enrollmentInput);
        $this->assertStringNotContainsString('required', $birthCertificateInput[0]);
        $this->assertStringNotContainsString('required', $enrollmentInput[0]);

        $this->put(route('applications.update'), $this->validApplicationPayload([
            'birth_certificate' => null,
            'certificate_enrollment' => null,
            'mother_name' => 'Existing Mother Name',
        ]))->assertRedirect(route('applications.myApplication'));

        $application->refresh();
        $this->assertSame('pending', $application->status);
        $this->assertSame($birthCertificate, $application->birth_certificate);
        $this->assertSame($enrollmentCertificate, $application->certificate_enrollment);
        $this->assertSame($gradeCertificate, $application->certificate_grade);
        $this->assertSame('Existing Mother Name', $application->mother_name);
        $this->assertSame('birth-certificate-old.pdf', $application->document_original_names['birth_certificate']);
        Storage::disk('local')->assertExists($birthCertificate);
        Storage::disk('local')->assertExists($enrollmentCertificate);
        Storage::disk('local')->assertExists($gradeCertificate);
    }

    public function test_applicant_can_replace_a_previously_submitted_document_when_reapplying(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $birthCertificate = 'applications/birth_certificates/birth-certificate-old.pdf';
        $enrollmentCertificate = 'applications/enrollment/enrollment-old.pdf';
        Storage::disk('local')->put($birthCertificate, 'original birth certificate');
        Storage::disk('local')->put($enrollmentCertificate, 'original enrollment certificate');
        $application = Application::factory()->for($user)->create([
            'status' => 'denied',
            'birth_certificate' => $birthCertificate,
            'certificate_enrollment' => $enrollmentCertificate,
            'document_original_names' => [
                'birth_certificate' => 'birth-certificate-old.pdf',
                'certificate_enrollment' => 'enrollment-old.pdf',
            ],
        ]);

        $this->actingAs($user)
            ->put(route('applications.update'), $this->validApplicationPayload([
                'birth_certificate' => UploadedFile::fake()->create('birth-certificate-replacement.pdf', 100, 'application/pdf'),
                'certificate_enrollment' => null,
            ]))
            ->assertRedirect(route('applications.myApplication'));

        $application->refresh();
        $this->assertSame('pending', $application->status);
        $this->assertNotSame($birthCertificate, $application->birth_certificate);
        $this->assertSame('birth-certificate-replacement.pdf', $application->document_original_names['birth_certificate']);
        Storage::disk('local')->assertMissing($birthCertificate);
        Storage::disk('local')->assertExists($application->birth_certificate);
        Storage::disk('local')->assertExists($enrollmentCertificate);
        $this->assertSame($enrollmentCertificate, $application->certificate_enrollment);
    }

    public function test_reapplication_still_requires_documents_that_are_not_on_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        Application::factory()->for($user)->create([
            'status' => 'denied',
            'birth_certificate' => null,
            'certificate_enrollment' => null,
        ]);

        $this->actingAs($user)
            ->put(route('applications.update'), $this->validApplicationPayload([
                'birth_certificate' => null,
                'certificate_enrollment' => null,
            ]))
            ->assertSessionHasErrors(['birth_certificate', 'certificate_enrollment']);
    }

    /** @test */
    public function user_sees_upload_button_when_birth_certificate_is_missing()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Application::factory()->for($user)->create(['birth_certificate' => null]);

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
            'birth_certificate' => 'applications/birth_certificates/sample.pdf',
            'status' => 'pending',
        ]);

        $this->get(route('applications.myApplication'))
            ->assertOk()
            ->assertSee('Birth Certificate')
            ->assertSee('View');
    }

    /** @test */
    public function applicant_requirements_page_only_shows_documents_requested_by_admin()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Application::factory()->for($user)->create([
            'birth_certificate' => 'applications/birth_certificates/sample.pdf',
            'certificate_enrollment' => 'applications/enrollment/sample.pdf',
        ]);

        $this->get(route('applicant.requirements'))
            ->assertOk()
            ->assertSee('View and manage your application requirements')
            ->assertSee('0 of 0 requested files submitted')
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
        Storage::fake('local');
        $user = User::factory()->create();
        $path = 'applications/birth_certificates/sample.pdf';
        $enrollmentPath = 'applications/enrollment/enrollment.pdf';
        Storage::disk('local')->put($path, 'sample pdf');
        Storage::disk('local')->put($enrollmentPath, 'sample enrollment pdf');

        $application = Application::factory()->for($user)->create([
            'birth_certificate' => $path,
            'certificate_enrollment' => $enrollmentPath,
            'status' => 'denied',
            'document_original_names' => [
                'birth_certificate' => 'birth-certificate.pdf',
                'certificate_enrollment' => 'enrollment-certificate.pdf',
            ],
        ]);

        $this->actingAs($user);

        $this->get(route('applications.myApplication'))
            ->assertOk()
            ->assertSee(route('applications.document.preview', ['application' => $application, 'document' => 'birth_certificate']), false)
            ->assertSee(route('applications.document.preview', ['application' => $application, 'document' => 'certificate_enrollment']), false)
            ->assertDontSee('openDocumentModal');

        $this->get(route('applications.document.preview', ['application' => $application, 'document' => 'birth_certificate']))
            ->assertOk()
            ->assertSee('Back to application')
            ->assertSee(route('applications.edit'), false)
            ->assertSee(route('applications.document.stream', ['application' => $application, 'document' => 'birth_certificate']), false)
            ->assertSee('Birth Certificate')
            ->assertSee('birth-certificate.pdf');

        $this->get(route('applications.document.preview', ['application' => $application, 'document' => 'certificate_enrollment']))
            ->assertOk()
            ->assertSee('Back to application')
            ->assertSee(route('applications.edit'), false)
            ->assertSee(route('applications.document.stream', ['application' => $application, 'document' => 'certificate_enrollment']), false)
            ->assertSee('Certificate of Enrollment')
            ->assertSee('enrollment-certificate.pdf');

        $this->get(route('applications.document.stream', ['application' => $application, 'document' => 'birth_certificate']))
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename=birth-certificate.pdf');

        $this->get(route('applications.document.stream', ['application' => $application, 'document' => 'certificate_enrollment']))
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename=enrollment-certificate.pdf');
    }

    /** @test */
    public function admin_sees_uploaded_birth_certificate_on_application_detail()
    {
        Storage::fake('local');
        $path = 'applications/birth_certificates/sample.pdf';
        Storage::disk('local')->put($path, "%PDF-1.4\nBirth Certificate");
        $admin = User::factory()->create(['role' => 'admin']);
        $application = Application::factory()->create([
            'birth_certificate' => $path,
            'document_original_names' => ['birth_certificate' => 'birth-certificate.pdf'],
        ]);

        $this->actingAs($admin);

        $this->get(route('admin.applications.show', $application))
            ->assertOk()
            ->assertSee('Birth Certificate')
            ->assertSee('View')
            ->assertSee('Download');

        $this->get(route('applications.document.preview', ['application' => $application, 'document' => 'birth_certificate']))
            ->assertOk()
            ->assertSee('Birth Certificate')
            ->assertSee('birth-certificate.pdf');
        $this->get(route('applications.document.stream', ['application' => $application, 'document' => 'birth_certificate']))
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename=birth-certificate.pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_application_documents_are_private_and_accessible_only_to_the_owner_or_admin(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $otherApplicant = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $path = 'applications/birth_certificates/private-document.pdf';
        Storage::disk('local')->put($path, 'private document bytes');
        $application = Application::factory()->for($owner)->create([
            'birth_certificate' => $path,
            'document_original_names' => ['birth_certificate' => 'private-document.pdf'],
        ]);
        $url = route('applications.document.stream', [
            'application' => $application,
            'document' => 'birth_certificate',
        ]);

        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($otherApplicant)->get($url)->assertForbidden();
        $this->actingAs($owner)
            ->get($url)
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename=private-document.pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($admin)
            ->get($url)
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename=private-document.pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_public_application_documents_can_be_moved_to_private_storage_idempotently(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $path = 'applications/birth_certificates/existing-document.pdf';
        Storage::disk('public')->put($path, 'existing document bytes');

        $this->assertSame(0, Artisan::call('spes:move-documents-to-private'));
        $this->assertSame(0, Artisan::call('spes:move-documents-to-private'));

        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame('existing document bytes', Storage::disk('local')->get($path));
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
            'birth_certificate' => UploadedFile::fake()->create('birth-certificate.pdf', 100, 'application/pdf'),
            'certificate_enrollment' => UploadedFile::fake()->create('certificate-of-enrollment.pdf', 100, 'application/pdf'),
        ], $overrides);
    }
}
