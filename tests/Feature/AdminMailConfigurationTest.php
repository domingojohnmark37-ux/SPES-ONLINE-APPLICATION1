<?php

namespace Tests\Feature;

use App\Mail\SpesMailConfigurationTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
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
        $admin = User::factory()->admin()->create([
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
        $applicant = User::factory()->create([]);

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
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.settings'))
            ->post(route('admin.settings.email-test'))
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHasErrors('email_test');

        Mail::assertSentCount(0);
    }

    public function test_admin_settings_show_selected_transport_and_safe_configuration_status(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.from.address' => 'peso@example.test',
            'mail.mailers.smtp.username' => 'peso@example.test',
            'mail.mailers.smtp.password' => 'configured-secret',
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSeeText('Selected mail transport: smtp')
            ->assertSeeText('Required configuration:')
            ->assertSeeText('Present')
            ->assertDontSee('configured-secret');
    }

    public function test_admin_test_reports_safe_authentication_diagnostic_and_logs_no_credentials(): void
    {
        $secret = 'smtp-private-password';
        config([
            'mail.default' => 'smtp',
            'mail.from.address' => 'peso@example.test',
            'mail.mailers.smtp.username' => 'peso@example.test',
            'mail.mailers.smtp.password' => $secret,
        ]);
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.test',
        ]);
        $mailer = \Mockery::mock();
        $mailer->shouldReceive('send')
            ->once()
            ->with(\Mockery::type(SpesMailConfigurationTest::class))
            ->andThrow(new TransportException("Authentication failed with {$secret}"));
        Mail::shouldReceive('to')->once()->with($admin->email)->andReturn($mailer);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context) use ($secret, $admin): bool {
                $this->assertSame('SMTP authentication failure', $context['failure_type']);
                $this->assertStringNotContainsString($secret, json_encode($context));
                $this->assertStringNotContainsString($admin->email, json_encode($context));

                return $message === 'SPES administrator mail configuration test failed.';
            });

        $this->actingAs($admin)
            ->from(route('admin.settings'))
            ->post(route('admin.settings.email-test'))
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHasErrors([
                'email_test' => 'The test email could not be delivered (SMTP authentication failure). Check the mail server logs and provider settings.',
            ]);
    }
}
