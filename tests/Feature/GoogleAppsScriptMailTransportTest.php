<?php

namespace Tests\Feature;

use App\Jobs\NotifyApplicantsJob;
use App\Mail\Transport\GoogleAppsScriptTransport;
use App\Models\ApplicantNotificationDelivery;
use App\Models\User;
use App\Notifications\ApplicantPortalUpdate;
use App\Services\ApplicantNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class GoogleAppsScriptMailTransportTest extends TestCase
{
    use RefreshDatabase;

    private const RELAY_URL = 'https://script.google.test/macros/s/test/exec';

    private const TOKEN = 'test-secret-token';

    public function test_successful_response_sends_and_posts_the_expected_payload(): void
    {
        Http::fake([self::RELAY_URL => Http::response(['ok' => true], 200)]);

        $this->transport()->send($this->email());

        Http::assertSent(function (HttpRequest $request): bool {
            $payload = $request->data();

            return $request->url() === self::RELAY_URL
                && $payload['token'] === self::TOKEN
                && $payload['to'] === ['applicant@example.test', 'second@example.test']
                && $payload['subject'] === 'Relay test'
                && $payload['html'] === '<p>Hello</p>'
                && $payload['text'] === 'Hello'
                && $payload['fromName'] === 'SPES Portal'
                && $payload['replyTo'] === 'reply@example.test';
        });
    }

    public function test_relay_quota_error_throws_transport_exception(): void
    {
        Http::fake([self::RELAY_URL => Http::response(['ok' => false, 'error' => 'quota'], 200)]);

        try {
            $this->transport()->send($this->email());
            $this->fail('A quota response should fail delivery.');
        } catch (TransportException $exception) {
            $this->assertStringContainsString('quota', $exception->getMessage());
            $this->assertStringNotContainsString(self::TOKEN, $exception->getMessage());
            $this->assertStringNotContainsString(self::RELAY_URL, $exception->getMessage());
        }
    }

    public function test_http_500_throws_transport_exception_without_revealing_url_or_token(): void
    {
        Http::fake([self::RELAY_URL => Http::response('server error', 500)]);

        try {
            $this->transport()->send($this->email());
            $this->fail('An HTTP 500 response should fail delivery.');
        } catch (TransportException $exception) {
            $this->assertStringContainsString('HTTP 500', $exception->getMessage());
            $this->assertStringNotContainsString(self::TOKEN, $exception->getMessage());
            $this->assertStringNotContainsString(self::RELAY_URL, $exception->getMessage());
        }
    }

    public function test_timeout_throws_transport_exception_without_revealing_url_or_token(): void
    {
        Http::fake(function (): never {
            throw new ConnectionException('Request timed out.');
        });

        try {
            $this->transport()->send($this->email());
            $this->fail('A timeout should fail delivery.');
        } catch (TransportException $exception) {
            $this->assertStringContainsString('request failed', $exception->getMessage());
            $this->assertStringNotContainsString(self::TOKEN, $exception->getMessage());
            $this->assertStringNotContainsString(self::RELAY_URL, $exception->getMessage());
        }
    }

    public function test_transport_rejects_attachments(): void
    {
        $email = $this->email()->attach('file contents', 'document.txt', 'text/plain');

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('does not support attachments');

        $this->transport()->send($email);
    }

    public function test_notification_quota_failure_is_logged_and_reaches_the_job_retry_policy(): void
    {
        config([
            'mail.default' => 'google_apps_script',
            'services.google_apps_script.url' => self::RELAY_URL,
            'services.google_apps_script.token' => self::TOKEN,
        ]);
        Http::fake([self::RELAY_URL => Http::response(['ok' => false, 'error' => 'quota'], 200)]);
        $user = User::factory()->create();
        $job = new NotifyApplicantsJob(
            'users',
            'quota-test-event',
            'notify_documents',
            'Application update',
            'There is an application update.',
            userIds: [$user->id],
        );

        try {
            $job->handle(app(ApplicantNotificationService::class));
            $this->fail('The quota error should be given to the queue retry policy.');
        } catch (TransportException $exception) {
            $this->assertStringContainsString('quota', $exception->getMessage());
        }

        $this->assertSame(3, $job->tries);
        $this->assertSame([30, 120, 300], $job->backoff);
        $this->assertSame('failed', ApplicantNotificationDelivery::query()
            ->where('user_id', $user->id)
            ->where('channel', 'mail')
            ->value('status'));
    }

    private function transport(): GoogleAppsScriptTransport
    {
        return new GoogleAppsScriptTransport(self::RELAY_URL, self::TOKEN);
    }

    private function email(): Email
    {
        return (new Email)
            ->from(new Address('sender@gmail.com', 'SPES Portal'))
            ->to('applicant@example.test', 'second@example.test')
            ->subject('Relay test')
            ->html('<p>Hello</p>')
            ->text('Hello')
            ->replyTo('reply@example.test');
    }
}
