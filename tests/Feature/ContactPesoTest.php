<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPesoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_applicant_can_view_peso_contact_information_without_request_form_or_history(): void
    {
        $applicant = User::factory()->create();

        $this->actingAs($applicant)
            ->get(route('contact-peso.index'))
            ->assertOk()
            ->assertSee('Contact Us')
            ->assertSee('PESO Contact Information')
            ->assertSee('Developer / Technical Support')
            ->assertSee('data-open-portal-help', false)
            ->assertSee('data-help-toggle', false)
            ->assertSee('portalHelpPanel', false)
            ->assertSee('09064522617')
            ->assertSee('pesolallo1581@gmail.com')
            ->assertSee('href="mailto:pesolallo1581@gmail.com"', false)
            ->assertSee('https://www.facebook.com/share/1D7MauEodf/', false)
            ->assertSee('Visit Facebook')
            ->assertSee('images/peso-facebook-page.png')
            ->assertSee('Screenshot of the official PESO Lal-lo Facebook page')
            ->assertSee('P. DUPAYA STREET, CENTRO, LAL-LO, CAGAYAN, 3509')
            ->assertSee('https://www.google.com/maps/embed?pb=', false)
            ->assertSee('<iframe', false)
            ->assertDontSee('Submit a Support Request')
            ->assertDontSee('My Support Requests')
            ->assertDontSee('Submit Request')
            ->assertDontSee('support-peso');
    }

    public function test_contact_peso_has_no_support_request_submission_or_detail_routes(): void
    {
        $applicant = User::factory()->create();

        $this->actingAs($applicant)
            ->post('/contact-peso/requests')
            ->assertNotFound();

        $this->get('/contact-peso/requests/1')->assertNotFound();
        $this->get('/contact-peso/requests/1/attachment')->assertNotFound();
        $this->post('/contact-peso/requests/1/replies')->assertNotFound();
    }

    public function test_admin_cannot_open_the_applicant_contact_peso_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('contact-peso.index'))
            ->assertForbidden();
    }
}
