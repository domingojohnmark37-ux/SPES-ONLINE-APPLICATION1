<?php

namespace Tests\Feature;

use App\Mail\SpesMailConfigurationTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminMailConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_send_a_real_configuration_test_to_their_registered_address(): void
    {
        Mail::fake();
        config([
            'mail.default' => 'smtp',
            'mail.from.address' => 'peso@example.test',
            'mail.mailers.smtp.username' => 'peso@example.test',
            'mail.mailers.smtp.password' => 'configured-secret',
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.test',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.settings.email-test'))
            ->assertRedirect()
            ->assertSessionHas('email_test_success');

        Mail::assertSent(SpesMailConfigurationTest::class, function (SpesMailConfigurationTest $mail) use ($admin): bool {
            return $mail->hasTo($admin->email);
        });
    }

    public function test_applicant_cannot_use_the_admin_email_test(): void
    {
        Mail::fake();
        $applicant = User::factory()->create(['role' => 'user']);

        $this->actingAs($applicant)
            ->post(route('admin.settings.email-test'))
            ->assertForbidden();

        Mail::assertSentCount(0);
    }

    public function test_admin_is_told_when_smtp_credentials_are_not_configured(): void
    {
        Mail::fake();
        config([
            'mail.default' => 'smtp',
            'mail.from.address' => 'peso@example.test',
            'mail.mailers.smtp.username' => null,
            'mail.mailers.smtp.password' => null,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.settings'))
            ->post(route('admin.settings.email-test'))
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHasErrors('email_test');

        Mail::assertSentCount(0);
    }
}
