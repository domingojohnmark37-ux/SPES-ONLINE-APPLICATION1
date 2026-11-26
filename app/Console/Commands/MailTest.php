<?php

namespace App\Console\Commands;

use App\Support\MailDeliveryDiagnostics;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailTest extends Command
{
    protected $signature = 'mail:test {email}';

    protected $description = 'Send a test email using the configured mailer';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $mailer = (string) config('mail.default');
        $mailerConfig = config("mail.mailers.{$mailer}", []);
        $url = $mailerConfig['url'] ?? null;
        $parsedUrl = is_string($url) ? parse_url($url) : false;
        $urlParts = is_array($parsedUrl) ? $parsedUrl : [];
        $isGoogleAppsScript = $mailer === 'google_apps_script';
        $host = $isGoogleAppsScript ? 'not shown' : ($mailerConfig['host'] ?? ($urlParts['host'] ?? 'not configured'));
        $port = $isGoogleAppsScript ? '443' : ($mailerConfig['port'] ?? ($urlParts['port'] ?? 'not configured'));
        $scheme = $isGoogleAppsScript
            ? 'https'
            : ($mailerConfig['scheme'] ?? ($urlParts['scheme'] ?? ($mailer === 'resend' ? 'https' : 'not configured')));

        try {
            Mail::raw('This is a test email from the SPES Online Application.', function ($message) use ($email): void {
                $message->to($email)->subject('SPES email delivery test');
            });
        } catch (Throwable $e) {
            $diagnostics = MailDeliveryDiagnostics::exceptionContext($e, $email);
            $this->error('Test email could not be sent.');
            $this->line("Mailer: {$mailer}");
            $this->line("Host: {$host}");
            $this->line("Port: {$port}");
            $this->line("Scheme: {$scheme}");
            $this->line('Failure: '.$diagnostics['failure_type']);
            $this->line('Exception: '.$diagnostics['exception_class']);
            $this->line('Diagnostic: '.$diagnostics['diagnostic']);

            return self::FAILURE;
        }

        $this->info("Test email sent successfully using [{$mailer}] to [{$email}].");

        return self::SUCCESS;
    }
}
