<?php

namespace Tests\Feature\Auth;

use App\Mail\PersonalSecurityRecoveryCode;
use App\Models\AuditLog;
use App\Models\PersonalizedSecurityVerification;
use App\Models\User;
use App\Services\PersonalSecurityAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PersonalSecurityVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_login_does_not_authenticate_until_the_personal_answer_is_verified(): void
    {
        $user = User::factory()->create();
        $this->createVerification($user);

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('redirect', route('login.personal-security.challenge', absolute: false));

        $this->assertGuest();
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
        $this->get(route('login.personal-security.challenge'))
            ->assertOk()
            ->assertSee('What was the name of your first school?');

        $this->post(route('login.personal-security.verify'), [
            'security_answer' => '  NORTH   STAR ',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_applicant_security_challenge_cannot_redirect_to_an_intended_admin_route(): void
    {
        $user = User::factory()->create();
        $this->createVerification($user);
        $this->get(route('admin.settings'))->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('login.personal-security.challenge'));

        $this->post(route('login.personal-security.verify'), [
            'security_answer' => 'North Star',
        ])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_incorrect_answers_are_generic_and_do_not_authenticate_the_user(): void
    {
        $user = User::factory()->create();
        $this->createVerification($user);
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->from(route('login.personal-security.challenge'))
            ->post(route('login.personal-security.verify'), ['security_answer' => 'incorrect answer'])
            ->assertRedirect(route('login.personal-security.challenge'))
            ->assertSessionHasErrors('security_answer');

        $this->assertGuest();
        $audit = AuditLog::query()
            ->where('action', 'Personal Security Verification Failed')
            ->latest('id')
            ->firstOrFail();
        $this->assertStringNotContainsString('incorrect answer', json_encode($audit->toArray()));
    }

    public function test_users_can_login_with_username_when_personal_verification_is_not_enabled(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->username,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_applicant_security_settings_show_the_personal_verification_status_and_setup_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.security'))
            ->assertOk()
            ->assertSee('Personalized Security Verification')
            ->assertSee(route('personal-security.configuration.update'), false)
            ->assertSee('id="personal_security_question"', false)
            ->assertSee('What was the name of your first school?')
            ->assertSee('What personal code word would you choose as your private answer?')
            ->assertSee('class="security-question-picker"', false)
            ->assertSee('Not enabled', false);
    }

    public function test_legacy_custom_security_question_remains_selected_in_the_question_dropdown(): void
    {
        $user = User::factory()->create();
        $this->createVerification($user, 'What is your favorite childhood place?');

        $this->actingAs($user)
            ->get(route('settings.security'))
            ->assertOk()
            ->assertSee('Previously saved question: What is your favorite childhood place?')
            ->assertSee('value="What is your favorite childhood place?" selected', false);
    }

    public function test_configuration_requires_current_password_and_stores_only_a_normalized_hash(): void
    {
        $user = User::factory()->create();
        $payload = [
            'question' => 'What was the name of your first school?',
            'security_answer' => 'North Star',
            'security_answer_confirmation' => 'north   star',
            'current_password' => 'password',
        ];

        $this->actingAs($user)
            ->put(route('personal-security.configuration.update'), $payload)
            ->assertRedirect();

        $verification = PersonalizedSecurityVerification::query()->firstOrFail();
        $this->assertSame($user->id, $verification->user_id);
        $this->assertTrue(Hash::check(
            hash('sha256', PersonalSecurityAnswer::normalize('NORTH STAR')),
            $verification->answer_hash,
        ));
        $this->assertStringNotContainsString('North Star', $verification->answer_hash);

        $this->put(route('personal-security.configuration.update'), array_merge($payload, [
            'current_password' => 'wrong-password',
        ]))->assertSessionHasErrors('current_password');
    }

    public function test_users_can_replace_or_disable_their_question_only_after_reauthentication(): void
    {
        $user = User::factory()->create();
        $this->createVerification($user);
        $this->actingAs($user);

        $this->put(route('personal-security.configuration.update'), [
            'question' => 'What is your favorite childhood place?',
            'security_answer' => 'Blue Mountain',
            'security_answer_confirmation' => 'blue mountain',
            'current_password' => 'password',
        ])->assertRedirect();
        $this->assertDatabaseHas('personalized_security_verifications', [
            'user_id' => $user->id,
            'question' => 'What is your favorite childhood place?',
        ]);

        $this->delete(route('personal-security.configuration.disable'), [
            'current_password' => 'wrong-password',
            'disable_confirmation' => 'DISABLE',
        ])->assertSessionHasErrors('current_password');
        $this->assertDatabaseHas('personalized_security_verifications', ['user_id' => $user->id]);

        $this->delete(route('personal-security.configuration.disable'), [
            'current_password' => 'password',
            'disable_confirmation' => 'DISABLE',
        ])->assertRedirect();
        $this->assertDatabaseMissing('personalized_security_verifications', ['user_id' => $user->id]);
    }

    public function test_email_recovery_requires_a_code_then_forces_a_new_question_before_other_pages(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $this->createVerification($user);
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->get(route('login.personal-security.recovery'))
            ->assertOk()
            ->assertSee('Send recovery code to my email');
        $this->post(route('login.personal-security.recovery.send'))->assertRedirect();
        Mail::assertSent(PersonalSecurityRecoveryCode::class);
        $code = Mail::sent(PersonalSecurityRecoveryCode::class)->first()->code;

        $this->post(route('login.personal-security.recovery.verify'), [
            'recovery_code' => $code,
        ])->assertRedirect(route('personal-security.recovery-setup'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseMissing('personalized_security_verifications', ['user_id' => $user->id]);
        $this->get(route('dashboard'))->assertRedirect(route('personal-security.recovery-setup'));
        $this->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('personal-security.recovery-setup'));

        $this->get(route('personal-security.recovery-setup'))
            ->assertOk()
            ->assertSee('id="required_security_question"', false)
            ->assertSee('What was the nickname only your family used for you?')
            ->assertSee('class="security-question-picker"', false);
        $this->put(route('personal-security.configuration.update'), [
            'question' => 'What is a memorable childhood place?',
            'security_answer' => 'Blue Mountain',
            'security_answer_confirmation' => 'Blue Mountain',
            'current_password' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseHas('personalized_security_verifications', ['user_id' => $user->id]);
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_five_incorrect_answers_lock_verification_without_blocking_recovery(): void
    {
        $user = User::factory()->create();
        $this->createVerification($user);
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.personal-security.verify'), [
                'security_answer' => 'not the answer',
            ]);
        }

        $this->assertGuest();
        $this->get(route('login.personal-security.challenge'))
            ->assertOk();
        $this->get(route('login.personal-security.recovery'))->assertOk();
        $this->post(route('login.personal-security.verify'), [
            'security_answer' => 'still not the answer',
        ])->assertSessionHasErrors('security_answer');
    }

    public function test_expired_pending_login_does_not_allow_the_challenge_or_recovery_routes(): void
    {
        $user = User::factory()->create();
        $this->createVerification($user);
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->travel(16)->minutes();
        $this->get(route('login.personal-security.challenge'))
            ->assertRedirect(route('login'));
        $this->get(route('login.personal-security.recovery'))
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    private function createVerification(User $user, string $question = 'What was the name of your first school?'): void
    {
        PersonalizedSecurityVerification::query()->create([
            'user_id' => $user->id,
            'question' => $question,
            'answer_hash' => Hash::make(hash('sha256', PersonalSecurityAnswer::normalize('North Star'))),
            'enabled_at' => now(),
        ]);
    }
}
