<?php

namespace Tests\Feature\Auth;

use App\Mail\RegistrationVerificationCode;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mail.default' => 'array']);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertDontSee('Full Name')
            ->assertSee(route('terms'))
            ->assertSeeText('I have read, understood, and agree to the Terms and Conditions.')
            ->assertSee('disabled', false);
    }

    public function test_terms_and_conditions_page_can_be_rendered(): void
    {
        $this->get(route('terms'))
            ->assertOk()
            ->assertSee('TERMS AND CONDITIONS')
            ->assertSee('1. Purpose of the Online Application')
            ->assertSee('11. Applicant Confirmation')
            ->assertSee('By continuing with your application, you acknowledge that you have read, understood, and agreed to these Terms and Conditions.');
    }

    public function test_registration_requires_terms_acceptance(): void
    {
        $response = $this->from('/register')->post('/register', [
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/register')
            ->assertSessionHasErrors([
                'terms_accepted' => 'Please agree to the Terms and Conditions before continuing.',
            ]);
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('pending_registrations', 0);
    }

    public function test_registration_rejects_passwords_that_are_too_short(): void
    {
        $this->assertPasswordIsRejected('Abcdef1!xy', 'The password must be at least 12 characters.');
    }

    public function test_registration_requires_an_uppercase_letter(): void
    {
        $this->assertPasswordIsRejected('long passphrase 7!', 'The password must contain at least one uppercase letter.');
    }

    public function test_registration_requires_a_number(): void
    {
        $this->assertPasswordIsRejected('Long passphrase !', 'The password must contain at least one number.');
    }

    public function test_registration_requires_a_symbol(): void
    {
        $this->assertPasswordIsRejected('Long passphrase 7', 'The password must contain at least one symbol.');
    }

    public function test_registration_rejects_breached_passwords(): void
    {
        $this->mockUncompromisedVerifier(false);

        $this->assertPasswordIsRejected(
            'Password 123!',
            'This password has appeared in a data breach. Please choose a different password.'
        );
    }

    public function test_signup_stores_only_pending_hashes_until_the_pin_is_verified(): void
    {
        Mail::fake();
        $this->mockUncompromisedVerifier(true);
        $password = '  Mañana rutas 7!  ';
        $pin = $this->beginRegistration('  JANE.DOE@EXAMPLE.COM ', $password);

        $pending = PendingRegistration::firstOrFail();
        $this->assertSame('jane.doe@example.com', $pending->email);
        $this->assertTrue(Hash::check($password, $pending->password_hash));
        $this->assertFalse(Hash::check($pin, $pending->password_hash));
        $this->assertTrue(Hash::check($pin, $pending->pin_hash));
        $this->assertNotSame($pin, $pending->pin_hash);
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();

        $this->post(route('registration.verify.submit'), ['pin' => $pin])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseCount('pending_registrations', 0);
        $this->assertDatabaseHas('users', [
            'email' => 'jane.doe@example.com',
        ]);
        $verifiedUser = auth()->user();
        $this->assertNotNull($verifiedUser->email_verified_at);
        $this->assertTrue(Hash::check($password, $verifiedUser->password));
        $this->assertSame('Jane Doe', $verifiedUser->name);
    }

    public function test_wrong_pin_is_rejected_and_counted(): void
    {
        Mail::fake();
        $this->mockUncompromisedVerifier(true);
        $this->beginRegistration();

        $this->post(route('registration.verify.submit'), ['pin' => '123456'])
            ->assertSessionHasErrors('pin');

        $this->assertSame(1, PendingRegistration::firstOrFail()->failed_attempts);
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_expired_pin_is_rejected_and_counted(): void
    {
        Mail::fake();
        $this->mockUncompromisedVerifier(true);
        $pin = $this->beginRegistration();
        $this->travel(11)->minutes();

        $this->post(route('registration.verify.submit'), ['pin' => $pin])
            ->assertSessionHasErrors('pin');

        $this->assertSame(1, PendingRegistration::firstOrFail()->failed_attempts);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_pin_cannot_be_reused_after_successful_verification(): void
    {
        Mail::fake();
        $this->mockUncompromisedVerifier(true);
        $pin = $this->beginRegistration();

        $this->post(route('registration.verify.submit'), ['pin' => $pin])
            ->assertRedirect(route('dashboard'));
        auth()->logout();

        $this->post(route('registration.verify.submit'), ['pin' => $pin])
            ->assertRedirect(route('register'));

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('pending_registrations', 0);
        $this->assertGuest();
    }

    public function test_five_wrong_pins_lock_the_pending_registration_until_resend(): void
    {
        Mail::fake();
        $this->mockUncompromisedVerifier(true);
        $pin = $this->beginRegistration();
        $wrongPin = $pin === '000000' ? '000001' : '000000';

        foreach (range(1, 5) as $_) {
            $this->post(route('registration.verify.submit'), ['pin' => $wrongPin])
                ->assertSessionHasErrors('pin');
        }

        $pending = PendingRegistration::firstOrFail();
        $this->assertSame(5, $pending->failed_attempts);
        $this->assertNull($pending->pin_hash);

        $this->travel(61)->seconds();
        $this->post(route('registration.verify.submit'), ['pin' => $pin])
            ->assertSessionHasErrors('pin');
        $this->assertSame(5, $pending->fresh()->failed_attempts);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_resending_a_pin_invalidates_the_previous_pin(): void
    {
        Mail::fake();
        $this->mockUncompromisedVerifier(true);
        $oldPin = $this->beginRegistration();
        $this->travel(61)->seconds();

        $this->post(route('registration.verify.resend'))
            ->assertSessionHas('status');

        $newPin = null;
        Mail::assertSent(RegistrationVerificationCode::class, 2);
        Mail::assertSent(RegistrationVerificationCode::class, function (RegistrationVerificationCode $mail) use (&$newPin) {
            $newPin = $mail->pin;

            return true;
        });
        $this->assertNotSame($oldPin, $newPin);

        $this->post(route('registration.verify.submit'), ['pin' => $oldPin])
            ->assertSessionHasErrors('pin');
        $this->post(route('registration.verify.submit'), ['pin' => $newPin])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('pending_registrations', 0);
    }

    public function test_resend_cooldown_and_total_resend_limit_are_enforced(): void
    {
        Mail::fake();
        $this->mockUncompromisedVerifier(true);
        $this->beginRegistration();

        $this->post(route('registration.verify.resend'))
            ->assertSessionHasErrors('pin');

        foreach (range(1, 3) as $_) {
            $this->travel(61)->seconds();
            $this->post(route('registration.verify.resend'))
                ->assertSessionHas('status');
        }

        $this->travel(61)->seconds();
        $this->post(route('registration.verify.resend'))
            ->assertSessionHasErrors('pin');

        $this->assertSame(3, PendingRegistration::firstOrFail()->resend_count);
        Mail::assertSent(RegistrationVerificationCode::class, 4);
    }

    public function test_existing_email_gets_the_same_verification_screen_without_duplicate_account(): void
    {
        Mail::fake();
        $this->mockUncompromisedVerifier(true);
        $existing = User::factory()->create(['email' => 'existing@example.com']);

        $this->post('/register', [
            'email' => $existing->email,
            'password' => 'Strong passphrase 123!',
            'password_confirmation' => 'Strong passphrase 123!',
            'terms_accepted' => '1',
        ])->assertRedirect(route('registration.verify'));

        $this->get(route('registration.verify'))
            ->assertOk()
            ->assertSee('Enter verification code')
            ->assertSee('If this email is eligible');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_pin_is_not_sent_through_the_log_mailer(): void
    {
        Mail::fake();
        $this->mockUncompromisedVerifier(true);
        config(['mail.default' => 'log']);
        $this->app['env'] = 'production';
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->from('/register')->post('/register', [
            'email' => 'secure@example.com',
            'password' => 'Strong passphrase 123!',
            'password_confirmation' => 'Strong passphrase 123!',
            'terms_accepted' => '1',
        ])->assertRedirect('/register')
            ->assertSessionHasErrors('email');

        Mail::assertNothingOutgoing();
        $this->assertDatabaseCount('pending_registrations', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_unverified_users_can_access_spes_portal_routes(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_new_users_can_register_with_a_valid_passphrase(): void
    {
        Mail::fake();
        $this->mockUncompromisedVerifier(true);
        $password = 'Strong passphrase 123!';
        $pin = $this->beginRegistration('jane.doe@example.com', $password);

        $this->post(route('registration.verify.submit'), ['pin' => $pin])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertTrue(Hash::check($password, auth()->user()->password));
        $this->assertSame('Jane Doe', auth()->user()->name);
        $this->assertSame('janedoe', auth()->user()->username);
    }

    public function test_registration_accepts_long_passwords_without_a_restrictive_maximum(): void
    {
        Mail::fake();
        $this->mockUncompromisedVerifier(true);
        $password = 'A'.str_repeat('long passphrase ', 10).'7!';
        $pin = $this->beginRegistration('long@example.com', $password);

        $this->post(route('registration.verify.submit'), ['pin' => $pin])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertTrue(Hash::check($password, auth()->user()->password));
    }

    public function test_existing_bcrypt_password_hashes_remain_verifiable(): void
    {
        $password = 'legacy password';
        $legacyHash = Hash::driver('bcrypt')->make($password);

        $this->assertTrue(Hash::check($password, $legacyHash));
    }

    private function beginRegistration(
        string $email = 'jane.doe@example.com',
        string $password = 'Strong passphrase 123!'
    ): string {
        $this->post('/register', [
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'terms_accepted' => '1',
        ])->assertRedirect(route('registration.verify'));

        $pin = null;
        Mail::assertSent(RegistrationVerificationCode::class, function (RegistrationVerificationCode $mail) use (&$pin) {
            $pin = $mail->pin;

            return true;
        });

        return $pin;
    }

    private function assertPasswordIsRejected(string $password, string $message): void
    {
        $response = $this->from('/register')->post('/register', [
            'email' => 'test@example.com',
            'password' => $password,
            'password_confirmation' => $password,
            'terms_accepted' => '1',
        ]);

        $response->assertRedirect('/register')
            ->assertSessionHasErrors(['password' => $message]);
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('pending_registrations', 0);
    }

    private function mockUncompromisedVerifier(bool $isUncompromised): void
    {
        $verifier = \Mockery::mock(UncompromisedVerifier::class);
        $verifier->shouldReceive('verify')->once()->andReturn($isUncompromised);
        $this->app->instance(UncompromisedVerifier::class, $verifier);
    }
}
