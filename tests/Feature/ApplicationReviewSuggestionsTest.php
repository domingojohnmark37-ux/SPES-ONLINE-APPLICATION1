<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationReviewSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_profile_name_and_suspicious_document_filename_suggestions(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('applications/resumes/birth.pdf', "%PDF-1.4\nbirth document");
        Storage::disk('public')->put('applications/enrollment/enrollment.pdf', "%PDF-1.4\nenrollment document");
        $applicant = User::factory()->create();
        $applicant->profile()->create([
            'first_name' => 'Christina',
            'last_name' => 'AgLuba',
            'date_of_birth' => '2000-01-01',
        ]);
        $application = Application::factory()->for($applicant)->create([
            'full_name' => 'Kristine Agluba',
            'first_name' => 'Kristine',
            'surname' => 'Agluba',
            'middle_name' => 'N/A',
            'birthday' => '2001-01-01',
            'resume' => 'applications/resumes/birth.pdf',
            'certificate_enrollment' => 'applications/enrollment/enrollment.pdf',
            'document_original_names' => [
                'resume' => 'certificate-of-enrollment.pdf',
                'certificate_enrollment' => 'enrollment-certificate.pdf',
            ],
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.applications.show', $application))
            ->assertOk()
            ->assertSee('Possible Applicant Errors')
            ->assertSee('<details', false)
            ->assertSee('<summary', false)
            ->assertSee('Could the applicant name differ from their profile?')
            ->assertSee('First name: profile says "Christina", application says "Kristine"')
            ->assertSee('Could the birth date differ from the profile?')
            ->assertSee('Could this be the wrong document?')
            ->assertSee('certificate-of-enrollment.pdf');
    }

    public function test_admin_sees_no_obvious_mismatch_note_when_profile_and_documents_match(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('applications/resumes/birth.pdf', "%PDF-1.4\nbirth document");
        Storage::disk('public')->put('applications/enrollment/enrollment.pdf', "%PDF-1.4\nenrollment document");
        $applicant = User::factory()->create();
        $applicant->profile()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);
        $application = Application::factory()->for($applicant)->create([
            'full_name' => 'Jane Doe',
            'first_name' => 'Jane',
            'surname' => 'Doe',
            'middle_name' => 'N/A',
            'resume' => 'applications/resumes/birth.pdf',
            'certificate_enrollment' => 'applications/enrollment/enrollment.pdf',
            'document_original_names' => [
                'resume' => 'jane-doe-birth-certificate.pdf',
                'certificate_enrollment' => 'jane-doe-enrollment-certificate.pdf',
            ],
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.applications.show', $application))
            ->assertOk()
            ->assertSee('No possible errors were detected by the automatic checks.')
            ->assertDontSee('Could the applicant name differ from their profile?')
            ->assertDontSee('Could this be the wrong document?');
    }

    public function test_admin_is_warned_when_a_required_document_is_unavailable_or_not_a_pdf(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('applications/resumes/birth.pdf', 'This is not a PDF document.');
        $applicant = User::factory()->create();
        $application = Application::factory()->for($applicant)->create([
            'resume' => 'applications/resumes/birth.pdf',
            'certificate_enrollment' => 'applications/enrollment/missing.pdf',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.applications.show', $application))
            ->assertOk()
            ->assertSee('Could this document have an invalid file type?')
            ->assertSee('Can the uploaded document be opened?');
    }
}
