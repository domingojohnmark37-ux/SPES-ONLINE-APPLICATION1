<?php

namespace App\Console\Commands;

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
        $host = $mailerConfig['host'] ?? ($urlParts['host'] ?? 'not configured');
        $port = $mailerConfig['port'] ?? ($urlParts['port'] ?? 'not configured');
        $scheme = $mailerConfig['scheme'] ?? ($urlParts['scheme'] ?? ($mailer === 'resend' ? 'https' : 'not configured'));

        try {
            Mail::raw('This is a test email from the SPES Online Application.', function ($message) use ($email): void {
                $message->to($email)->subject('SPES email delivery test');
            });
        } catch (Throwable $e) {
            $this->error('Test email could not be sent.');
            $this->line("Mailer: {$mailer}");
            $this->line("Host: {$host}");
            $this->line("Port: {$port}");
            $this->line("Scheme: {$scheme}");
            $this->line('Exception: '.$e::class.': '.$this->redactSecrets($e->getMessage()));

            return self::FAILURE;
        }

        $this->info("Test email sent successfully using [{$mailer}] to [{$email}].");

        return self::SUCCESS;
    }

    private function redactSecrets(string $message): string
    {
        $secrets = array_merge(
            $this->configuredSecrets(config('services', [])),
            $this->configuredSecrets(config('mail.mailers', [])),
        );

        foreach (array_unique($secrets) as $secret) {
            if (is_string($secret) && $secret !== '') {
                $message = str_replace($secret, 'REDACTED', $message);
                $message = str_replace(rawurlencode($secret), 'REDACTED', $message);
            }
        }

        $url = config("mail.mailers.".config('mail.default').".url");
        if (is_string($url) && ($parts = parse_url($url)) !== false) {
            foreach (['user', 'pass'] as $part) {
                if (isset($parts[$part]) && $parts[$part] !== '') {
                    $message = str_replace(urldecode($parts[$part]), 'REDACTED', $message);
                }
            }

            parse_str($parts['query'] ?? '', $query);
            foreach ($query as $key => $value) {
                if (preg_match('/password|secret|(?:api[_-]?)?key|access[_-]?key|token/i', (string) $key)
                    && is_string($value)
                    && $value !== '') {
                    $message = str_replace(urldecode($value), 'REDACTED', $message);
                }
            }
        }

        return $message;
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return list<string>
     */
    private function configuredSecrets(array $configuration): array
    {
        $secrets = [];

        foreach ($configuration as $key => $value) {
            if (is_array($value)) {
                $secrets = array_merge($secrets, $this->configuredSecrets($value));
            } elseif (
                is_string($value)
                && $value !== ''
                && preg_match('/password|secret|(?:api[_-]?)?key|access[_-]?key|token/i', (string) $key)
            ) {
                $secrets[] = $value;
            }
        }

        return $secrets;
    }
}
