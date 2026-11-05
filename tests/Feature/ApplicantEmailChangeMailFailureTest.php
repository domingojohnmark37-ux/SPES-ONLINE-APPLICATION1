<?php

namespace Tests\Feature;

use App\Models\PendingEmailChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class ApplicantEmailChangeMailFailureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mail.default' => 'array']);
    }

    public function test_email_change_mail_failure_returns_validation_error_without_pending_record(): void
    {
        $user = User::factory()->create();
        Mail::shouldReceive('to')->once()->andThrow(new TransportException('SMTP down'));

        $this->actingAs($user)->postJson(route('settings.email.request'), [
            'email' => 'new.address@example.com',
            'current_password' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email')
            ->assertJsonPath('errors.email.0', 'We could not send the verification email. Please try again later.');

        $this->assertDatabaseCount('pending_email_changes', 0);
        $this->assertSame($user->email, $user->fresh()->email);
    }
}
