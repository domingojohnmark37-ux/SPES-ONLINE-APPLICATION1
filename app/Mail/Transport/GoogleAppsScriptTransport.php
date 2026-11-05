<?php

namespace App\Mail\Transport;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class GoogleAppsScriptTransport extends AbstractTransport
{
    public function __construct(
        private readonly string $url,
        private readonly string $token,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $from = $email->getFrom();
        $replyTo = $email->getReplyTo();

        if ($email->getAttachments() !== []) {
            throw new TransportException('Google Apps Script mail relay does not support attachments.');
        }

        try {
            $response = Http::withOptions(['allow_redirects' => true])
                ->timeout(20)
                ->asJson()
                ->post($this->url, [
                    'token' => $this->token,
                    'to' => array_map(
                        static fn (Address $address): string => $address->getAddress(),
                        $email->getTo(),
                    ),
                    'subject' => $email->getSubject(),
                    'html' => $this->bodyToString($email->getHtmlBody()),
                    'text' => $this->bodyToString($email->getTextBody()),
                    'fromName' => ($from[0] ?? null)?->getName() ?? '',
                    'replyTo' => ($replyTo[0] ?? null)?->getAddress(),
                ]);
        } catch (ConnectionException) {
            throw new TransportException('Google Apps Script mail relay request failed.');
        }

        $result = $response->json();
        if ($response->successful() && is_array($result) && ($result['ok'] ?? null) === true) {
            return;
        }

        $error = is_array($result) && is_string($result['error'] ?? null)
            ? $this->safeError($result['error'])
            : null;
        $detail = $error !== null && $error !== ''
            ? " ({$error})"
            : " (HTTP {$response->status()})";

        throw new TransportException('Google Apps Script mail relay rejected the message'.$detail.'.');
    }

    public function __toString(): string
    {
        return 'google-apps-script';
    }

    private function bodyToString(mixed $body): string
    {
        if (is_resource($body)) {
            $contents = stream_get_contents($body);
            if ($contents === false) {
                throw new TransportException('Google Apps Script mail relay could not read the message body.');
            }

            return $contents;
        }

        return is_string($body) ? $body : '';
    }

    private function safeError(string $error): string
    {
        $sensitiveValues = array_values(array_filter([
            $this->token,
            rawurlencode($this->token),
            $this->url,
            rawurlencode($this->url),
        ], static fn (string $value): bool => $value !== ''));
        $error = str_replace(
            $sensitiveValues,
            '[redacted]',
            $error,
        );

        return mb_substr(preg_replace('/[\r\n\x00-\x1F\x7F]+/u', ' ', $error) ?? '', 0, 120);
    }
}
