<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class MailTestCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_mail_test_command_succeeds_with_array_mailer(): void
    {
        config(['mail.default' => 'array']);

        $this->artisan('mail:test', ['email' => 'applicant@example.test'])
            ->expectsOutputToContain('Test email sent successfully using [array]')
            ->assertExitCode(0);
    }

    public function test_mail_test_command_reports_transport_details_without_exposing_secrets(): void
    {
        config([
            'mail.default' => 'resend',
            'services.resend.key' => 'resend-test-secret',
        ]);
        Mail::shouldReceive('raw')
            ->once()
            ->andThrow(new TransportException('Connection failed with resend-test-secret'));

        $exitCode = Artisan::call('mail:test', ['email' => 'applicant@example.test']);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Mailer: resend', $output);
        $this->assertStringContainsString('Host: not configured', $output);
        $this->assertStringContainsString('Port: not configured', $output);
        $this->assertStringContainsString('Scheme: https', $output);
        $this->assertStringContainsString('Exception: '.TransportException::class, $output);
        $this->assertStringContainsString('Connection failed with REDACTED', $output);
        $this->assertStringNotContainsString('resend-test-secret', $output);
    }
}
