<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
            'status' => 'pending',
        ]);
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
            'forms_step' => 2,
        ]);

        $this->actingAs($user);

        $response = $this->put(route('applications.update'), $this->validApplicationPayload([
            'full_name' => 'Juan Updated',
        ]));

        $response->assertRedirect(route('applications.myApplication'));

        $application->refresh();
        $this->assertSame('pending', $application->status);
        $this->assertNull($application->admin_comment);
        $this->assertSame(0, $application->forms_step);
        $this->assertSame('Juan Updated', $application->full_name);
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
            ->assertSee('View');
    }

    /** @test */
    public function user_document_preview_uses_birth_certificate_name()
    {
        $user = User::factory()->create();
        $path = 'applications/resumes/sample.pdf';
        \Illuminate\Support\Facades\Storage::disk('public')->put($path, 'sample pdf');

        $application = Application::factory()->for($user)->create([
            'resume' => $path,
        ]);

        $this->actingAs($user);

        $this->get(route('applications.document.view', ['application' => $application->id, 'document' => 'resume']))
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename="birth-certificate.pdf"');
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
