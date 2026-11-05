<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
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

    public function test_mail_test_command_sends_through_google_apps_script(): void
    {
        config([
            'mail.default' => 'google_apps_script',
            'services.google_apps_script.url' => 'https://script.google.test/macros/s/test/exec',
            'services.google_apps_script.token' => 'mail-test-token',
        ]);
        Http::fake([
            'https://script.google.test/macros/s/test/exec' => Http::response(['ok' => true], 200),
        ]);

        $this->artisan('mail:test', ['email' => 'applicant@example.test'])
            ->expectsOutputToContain('Test email sent successfully using [google_apps_script]')
            ->assertExitCode(0);

        Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool =>
            $request->data()['to'] === ['applicant@example.test']
            && $request->data()['token'] === 'mail-test-token'
        );
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

    public function test_google_apps_script_diagnostics_never_print_its_url_or_token(): void
    {
        $url = 'https://script.google.test/macros/s/private-deployment/exec';
        $token = 'private-relay-token';
        config([
            'mail.default' => 'google_apps_script',
            'services.google_apps_script.url' => $url,
            'services.google_apps_script.token' => $token,
        ]);
        Mail::shouldReceive('raw')
            ->once()
            ->andThrow(new TransportException("Failed to connect to {$url} using {$token}"));

        $exitCode = Artisan::call('mail:test', ['email' => 'applicant@example.test']);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Scheme: https', $output);
        $this->assertStringNotContainsString($url, $output);
        $this->assertStringNotContainsString($token, $output);
    }
}
