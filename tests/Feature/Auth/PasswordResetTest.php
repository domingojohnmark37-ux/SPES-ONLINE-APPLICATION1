<?php

namespace Tests\Feature\Auth;

use App\Mail\PasswordResetVerificationCode;
use App\Models\PendingPasswordReset;
use App\Models\User;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')->assertOk();
    }

    public function test_entering_email_opens_the_new_password_page(): void
    {
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('password.reset'))
            ->assertSessionHas('password_reset_email', $user->email);

        $this->get(route('password.reset'))
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee('Confirm Password');
    }

    public function test_submitting_new_password_sends_an_otp_without_changing_password(): void
    {
        Mail::fake();
        $this->allowUncompromisedPasswords();
        $user = User::factory()->create();

        $this->startResetFor($user);
        $this->post(route('password.store'), $this->newPasswordPayload())
            ->assertRedirect(route('password.verify'));

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
        $this->assertDatabaseHas('pending_password_resets', [
            'user_id' => $user->id,
            'failed_attempts' => 0,
        ]);
        Mail::assertSent(PasswordResetVerificationCode::class, fn (PasswordResetVerificationCode $mail): bool => $mail->hasTo($user->email));

        $this->get(route('password.verify'))
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee('Verify code and change password');
    }

    public function test_password_changes_only_after_valid_email_otp(): void
    {
        Mail::fake();
        $this->allowUncompromisedPasswords();
        $user = User::factory()->create();

        $this->startResetFor($user);
        $this->post(route('password.store'), $this->newPasswordPayload())
            ->assertRedirect(route('password.verify'));

        Mail::assertSent(PasswordResetVerificationCode::class, function (PasswordResetVerificationCode $mail) use ($user): bool {
            $this->post(route('password.verify.submit'), ['pin' => '000000']);
            $this->assertTrue(Hash::check('password', $user->refresh()->password));

            $response = $this->post(route('password.verify.submit'), ['pin' => $mail->pin]);
            $response->assertRedirect(route('login'))
                ->assertSessionHasNoErrors();

            return true;
        });

        $this->assertTrue(Hash::check('New secure passphrase 29!', $user->refresh()->password));
        $this->assertDatabaseMissing('pending_password_resets', ['user_id' => $user->id]);
    }

    public function test_expired_otp_cannot_change_password(): void
    {
        Mail::fake();
        $this->allowUncompromisedPasswords();
        $user = User::factory()->create();

        $this->startResetFor($user);
        $this->post(route('password.store'), $this->newPasswordPayload())
            ->assertRedirect(route('password.verify'));

        $pending = PendingPasswordReset::query()->where('user_id', $user->id)->firstOrFail();
        $pending->update(['pin_expires_at' => now()->subMinute()]);

        $this->post(route('password.verify.submit'), ['pin' => '123456'])
            ->assertSessionHasErrors('pin');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    private function startResetFor(User $user): void
    {
        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('password.reset'));
    }

    private function newPasswordPayload(): array
    {
        return [
            'password' => 'New secure passphrase 29!',
            'password_confirmation' => 'New secure passphrase 29!',
        ];
    }

    private function allowUncompromisedPasswords(): void
    {
        $verifier = \Mockery::mock(UncompromisedVerifier::class);
        $verifier->shouldReceive('verify')->andReturn(true);
        $this->app->instance(UncompromisedVerifier::class, $verifier);
    }
}
