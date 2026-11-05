<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateTestApplicantCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_a_verified_applicant_without_sending_email(): void
    {
        $this->artisan('spes:create-test-applicant', ['email' => 'Test.Applicant@example.test'])
            ->expectsQuestion('Name for the test applicant', 'Portal Test Applicant')
            ->expectsQuestion('Choose a password (at least 12 characters, with an uppercase letter, number, and symbol)', 'Secure Test Password 27!')
            ->expectsQuestion('Confirm the password', 'Secure Test Password 27!')
            ->expectsOutputToContain('Verified applicant account created for test.applicant@example.test.')
            ->assertExitCode(0);

        $user = User::query()->where('email', 'test.applicant@example.test')->firstOrFail();
        $this->assertSame('user', $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('Secure Test Password 27!', $user->password));
    }

    public function test_command_refuses_to_change_an_existing_account(): void
    {
        $existing = User::factory()->create(['email' => 'existing@example.test']);
        $originalHash = $existing->password;

        $this->artisan('spes:create-test-applicant', ['email' => $existing->email])
            ->expectsOutputToContain('An account already exists for that email. No account was changed.')
            ->assertExitCode(1);

        $this->assertSame($originalHash, $existing->fresh()->password);
    }

    public function test_command_rejects_an_invalid_email_without_prompting_for_credentials(): void
    {
        $this->artisan('spes:create-test-applicant', ['email' => 'not-an-email'])
            ->expectsOutputToContain('Provide a valid email address.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }
}
