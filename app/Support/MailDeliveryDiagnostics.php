<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException as HttpConnectionException;
use Symfony\Component\Mailer\Exception\AuthenticationFailedException;
use Throwable;

class MailDeliveryDiagnostics
{
    /**
     * @return array{mailer: string, ready: bool, missing: list<string>}
     */
    public static function configurationStatus(): array
    {
        $mailer = (string) config('mail.default');
        $missing = [];

        if ($mailer === 'smtp') {
            $smtp = config('mail.mailers.smtp', []);
            $missing = self::missingSettings([
                'MAIL_HOST' => filled($smtp['host'] ?? null),
                'MAIL_PORT' => is_numeric($smtp['port'] ?? null)
                    && (int) $smtp['port'] >= 1
                    && (int) $smtp['port'] <= 65535,
                'MAIL_USERNAME' => filled($smtp['username'] ?? null),
                'MAIL_PASSWORD' => filled($smtp['password'] ?? null),
                'MAIL_FROM_ADDRESS' => filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL) !== false,
            ]);
        } elseif ($mailer === 'google_apps_script') {
            $relayUrl = config('services.google_apps_script.url');
            $missing = self::missingSettings([
                'MAIL_FROM_ADDRESS' => filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL) !== false,
                'GOOGLE_APPS_SCRIPT_MAIL_URL' => is_string($relayUrl)
                    && filter_var($relayUrl, FILTER_VALIDATE_URL) !== false
                    && parse_url($relayUrl, PHP_URL_SCHEME) === 'https',
                'GOOGLE_APPS_SCRIPT_MAIL_TOKEN' => filled(config('services.google_apps_script.token')),
            ]);
        } elseif ($mailer === 'resend') {
            $missing = self::missingSettings([
                'MAIL_FROM_ADDRESS' => filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL) !== false,
                'RESEND_API_KEY' => filled(config('services.resend.key')),
            ]);
        } elseif ($mailer === 'postmark') {
            $missing = self::missingSettings([
                'MAIL_FROM_ADDRESS' => filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL) !== false,
                'POSTMARK_API_KEY' => filled(config('services.postmark.key')),
            ]);
        } elseif ($mailer === 'ses') {
            $missing = self::missingSettings([
                'MAIL_FROM_ADDRESS' => filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL) !== false,
                'AWS_ACCESS_KEY_ID' => filled(config('services.ses.key')),
                'AWS_SECRET_ACCESS_KEY' => filled(config('services.ses.secret')),
                'AWS_DEFAULT_REGION' => filled(config('services.ses.region')),
            ]);
        } elseif (in_array($mailer, ['log', 'array'], true)) {
            $missing[] = 'MAIL_MAILER (must select a delivery transport)';
        } elseif (! filled($mailer) || ! is_array(config("mail.mailers.{$mailer}"))) {
            $missing[] = 'MAIL_MAILER (unsupported transport)';
        } else {
            $missing = self::missingSettings([
                'MAIL_FROM_ADDRESS' => filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL) !== false,
            ]);
        }

        return [
            'mailer' => $mailer,
            'ready' => $missing === [],
            'missing' => $missing,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function exceptionContext(Throwable $exception, ?string $recipient = null, ?string $verificationCode = null): array
    {
        $messages = [];
        $classes = [];
        $current = $exception;

        for ($depth = 0; $current !== null && $depth < 5; $depth++, $current = $current->getPrevious()) {
            $classes[] = $current::class;
            $messages[] = $current->getMessage();
        }

        $message = implode(' | ', array_filter($messages));
        $mailer = (string) config('mail.default');

        return [
            'mailer' => $mailer,
            'failure_type' => self::failureType($mailer, $exception, $message),
            'exception_class' => $exception::class,
            'previous_exception_classes' => implode(', ', array_slice($classes, 1)),
            'diagnostic' => self::sanitize($message, $recipient, $verificationCode),
        ];
    }

    /**
     * @param  array<string, bool>  $settings
     * @return list<string>
     */
    private static function missingSettings(array $settings): array
    {
        return array_keys(array_filter($settings, static fn (bool $present): bool => ! $present));
    }

    private static function failureType(string $mailer, Throwable $exception, string $message): string
    {
        if ($mailer === 'google_apps_script') {
            return 'Google Apps Script failure';
        }

        $details = strtolower($message);

        if ($exception instanceof AuthenticationFailedException
            || preg_match('/authentication|auth(?:entication)? failed|535(?:[- ]|$)|5\\.7\\.8|invalid credentials/i', $details)) {
            return 'SMTP authentication failure';
        }

        if (preg_match('/starttls|\\btls\\b|\\bssl\\b|certificate|crypto(?:graphic)?/i', $details)) {
            return 'TLS negotiation failure';
        }

        if ($exception instanceof HttpConnectionException
            || preg_match('/connection refused|connection failed|could not connect|connection timed out|timed out|timeout|name resolution|dns/i', $details)) {
            return 'SMTP connection failure';
        }

        if (preg_match('/sender|from address|mail from/i', $details)) {
            return 'Invalid sender';
        }

        if (preg_match('/recipient|rcpt to|5\\.1\\.1|mailbox unavailable/i', $details)) {
            return 'Invalid recipient';
        }

        if (preg_match('/invalid.{0,24}address|address.{0,24}invalid/i', $details)) {
            return 'Invalid recipient';
        }

        if ($exception instanceof \InvalidArgumentException) {
            return 'Mail configuration error';
        }

        if (preg_match('/rejected|\\b[45]\\d\\d(?:[- .]|$)/i', $details)) {
            return 'Mail server rejection';
        }

        return 'Other delivery exception';
    }

    private static function sanitize(string $message, ?string $recipient, ?string $verificationCode): string
    {
        $secrets = self::configuredSecrets(config('mail.mailers', []));
        $secrets = array_merge($secrets, self::configuredSecrets(config('services', [])));
        $secrets = array_merge(
            $secrets,
            self::configuredUrls(config('mail.mailers', [])),
            self::configuredUrls(config('services', [])),
        );
        $secrets[] = (string) config('mail.from.address', '');
        $secrets[] = (string) config('mail.mailers.'.config('mail.default').'.username', '');
        $secrets[] = (string) $recipient;
        $secrets[] = (string) $verificationCode;
        $secrets = array_values(array_unique(array_filter($secrets, static fn (string $value): bool => $value !== '')));

        foreach ($secrets as $secret) {
            $message = str_replace(
                [$secret, rawurlencode($secret)],
                '[redacted]',
                $message,
            );
        }

        $message = preg_replace('/\\b(password|token|secret|api[_-]?key)\\s*[:=]\\s*[^\\s,;]+/i', '$1=[redacted]', $message) ?? $message;

        return mb_substr(preg_replace('/[\\r\\n\\x00-\\x1F\\x7F]+/u', ' ', $message) ?? '', 0, 500);
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return list<string>
     */
    private static function configuredSecrets(array $configuration): array
    {
        $secrets = [];

        foreach ($configuration as $key => $value) {
            if (is_array($value)) {
                $secrets = array_merge($secrets, self::configuredSecrets($value));
            } elseif (
                is_string($value)
                && $value !== ''
                && preg_match('/password|secret|token|(?:api[_-]?)?key|access[_-]?key/i', (string) $key)
            ) {
                $secrets[] = $value;
            }
        }

        return $secrets;
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return list<string>
     */
    private static function configuredUrls(array $configuration): array
    {
        $urls = [];

        foreach ($configuration as $key => $value) {
            if (is_array($value)) {
                $urls = array_merge($urls, self::configuredUrls($value));
            } elseif (is_string($value) && $value !== '' && preg_match('/url/i', (string) $key)) {
                $urls[] = $value;
            }
        }

        return $urls;
    }
}
