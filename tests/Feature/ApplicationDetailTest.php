<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_detail_does_not_display_automated_possible_errors(): void
    {
        $applicant = User::factory()->create();
        $application = Application::factory()->for($applicant)->create([
            'full_name' => 'Jamie Applicant',
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.applications.show', $application))
            ->assertOk()
            ->assertSee('Application Detail')
            ->assertSee('Jamie Applicant')
            ->assertSee('Uploaded Documents')
            ->assertDontSee('Possible Applicant Errors')
            ->assertDontSee('No possible errors were detected')
            ->assertDontSee('FAQ-style suggestions');
    }

    public function test_admin_feedback_contains_scrollable_wrapping_applicant_issue_dropdown(): void
    {
        $applicant = User::factory()->create();
        $application = Application::factory()->for($applicant)->create();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.applications.show', $application))
            ->assertOk()
            ->assertSee('Choose a common issue to add it to the feedback')
            ->assertSee('data-feedback-picker', false)
            ->assertSee('data-feedback-option="Invalid email address"', false)
            ->assertSee('data-feedback-option="Certificate belongs to a previous school year when a current one is required"', false)
            ->assertSee('data-feedback-option="Cropped or incomplete document"', false)
            ->assertSee('data-feedback-selected', false);

        $this->assertStringContainsString('max-height:240px; overflow-y:auto', $response->getContent());
        $this->assertStringContainsString('white-space:normal; overflow-wrap:anywhere', $response->getContent());
    }
}
