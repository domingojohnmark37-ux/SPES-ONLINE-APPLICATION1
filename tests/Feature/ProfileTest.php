<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response
            ->assertOk()
            ->assertSee('applicant-profile-page', false)
            ->assertSee('profile-form-section', false)
            ->assertSee('html[data-theme="dark"] .applicant-profile-page .page-title h1', false)
            ->assertSee('Change your email in', false)
            ->assertSee('Enter your mobile number here. You can change it in Settings after saving.')
            ->assertSee('id="contact_number" name="contact_number" type="text" value="" required="required"', false)
            ->assertDontSee('id="contact_number" name="contact_number" type="text" value="" readonly', false)
            ->assertSee('name="middle_name"', false)
            ->assertSee('name="social_media"', false)
            ->assertSee('id="father_name" name="father_name" type="text" required="required"', false)
            ->assertSee('id="father_contact_number" name="father_contact_number" type="text" required="required"', false)
            ->assertSee('id="father_occupation" name="father_occupation" type="text" required="required"', false)
            ->assertSee('id="mother_name" name="mother_name" type="text" required="required"', false)
            ->assertSee('id="mother_contact_number" name="mother_contact_number" type="text" required="required"', false)
            ->assertSee('id="mother_occupation" name="mother_occupation" type="text" required="required"', false)
            ->assertSee('Current parent status', false)
            ->assertDontSee('GSIS beneficiary or relationship')
            ->assertDontSee('name="gsis_beneficiary"', false);
    }

    public function test_applicant_profile_form_cannot_bypass_email_verification(): void
    {
        $user = User::factory()->create();
        $originalName = $user->name;

        $this
            ->actingAs($user)
            ->patch('/profile', [
                ...$this->validApplicantProfile($user, [
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                ]),
            ])
            ->assertSessionHasErrors('email');

        $user->refresh();

        $this->assertSame($originalName, $user->name);
        $this->assertNotSame('test@example.com', $user->email);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_profile_information_can_be_updated_without_changing_email(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['contact_number' => '09179999999']);
        $this->actingAs($user)->get('/profile')
            ->assertSee('Change your mobile number in')
            ->assertSee('href="'.route('settings.account').'#mobile-number-settings"', false);

        $this
            ->patch('/profile', [
                ...$this->validApplicantProfile($user, [
                    'name' => 'Test User',
                    'contact_number' => '09179999999',
                ]),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('Test User', $user->fresh()->name);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $profile = $user->profile()->firstOrFail();
        $this->assertSame('09179999999', $profile->contact_number);
        $this->assertSame('Applicant', $profile->last_name);
        $this->assertSame('Test', $profile->first_name);
        $this->assertSame('Lal-lo, Cagayan', $profile->place_of_birth);
        $this->assertSame('Father Test', $profile->father_name);
    }

    public function test_applicant_can_save_profile_while_application_period_is_closed(): void
    {
        $user = User::factory()->create();
        SystemSetting::current()->update([
            'application_start_date' => now()->subDays(3),
            'application_end_date' => now()->subDay(),
        ]);

        $this->assertFalse(SystemSetting::current()->isApplicationOpen());

        $this->actingAs($user)
            ->patch('/profile', $this->validApplicantProfile($user))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('Applicant', $user->profile()->firstOrFail()->last_name);
    }

    public function test_applicant_can_enter_mobile_number_on_profile_only_when_not_already_set(): void
    {
        $user = User::factory()->create();
        $profile = $this->validApplicantProfile($user, ['contact_number' => '09123456789']);

        $this->actingAs($user)
            ->patch('/profile', $profile)
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('09123456789', $user->profile()->firstOrFail()->contact_number);

        $this->from('/profile')
            ->patch('/profile', [
                ...$profile,
                'contact_number' => '09129999999',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasErrors('contact_number');

        $this->assertSame('09123456789', $user->profile()->firstOrFail()->contact_number);
    }

    public function test_profile_update_does_not_accept_gsis_beneficiary_information(): void
    {
        $user = User::factory()->create();
        $originalValue = 'Existing beneficiary';
        $user->forceFill(['gsis_beneficiary' => $originalValue])->save();

        $this        ->actingAs($user)
        ->patch('/profile', [
            ...$this->validApplicantProfile($user),
            'gsis_beneficiary' => 'New submitted value',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame($originalValue, $user->fresh()->gsis_beneficiary);
    }

    public function test_applicant_profile_fields_are_required_server_side(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'email' => $user->email,
                'contact_number' => '',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasErrors([
                'last_name',
                'first_name',
                'middle_name',
                'sex',
                'date_of_birth',
                'place_of_birth',
                'status',
                'citizenship',
                'social_media',
                'contact_number',
                'present_address',
                'permanent_address',
                'applicant_category',
                'father_name',
                'father_contact_number',
                'father_occupation',
                'mother_name',
                'mother_contact_number',
                'mother_occupation',
                'parent_status_details',
            ]);

        $this->get('/profile')
            ->assertSee('Your profile was not saved. Please correct the following and try again:')
            ->assertSee('The last name field is required.');
    }

    public function test_applicant_must_select_at_least_one_parent_status(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                ...$this->validApplicantProfile($user),
                'parent_status_details' => [],
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasErrors('parent_status_details');
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', $this->validApplicantProfile($user, ['name' => 'Test User']));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    private function validApplicantProfile(User $user, array $overrides = []): array
    {
        return array_merge([
            'name' => $user->name,
            'email' => $user->email,
            'last_name' => 'Applicant',
            'first_name' => 'Test',
            'middle_name' => 'Sample',
            'sex' => 'Female',
            'date_of_birth' => '2000-01-01',
            'place_of_birth' => 'Lal-lo, Cagayan',
            'status' => 'Single',
            'citizenship' => 'Filipino',
            'social_media' => 'facebook.com/test',
            'contact_number' => '09123456789',
            'present_address' => 'Centro, Lal-lo, Cagayan',
            'permanent_address' => 'Centro, Lal-lo, Cagayan',
            'applicant_category' => 'student',
            'father_name' => 'Father Test',
            'father_contact_number' => '09123456780',
            'father_occupation' => 'Farmer',
            'mother_name' => 'Mother Test',
            'mother_contact_number' => '09123456781',
            'mother_occupation' => 'Vendor',
            'parent_status_details' => ['Living Together'],
        ], $overrides);
    }
}
