<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicantSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_applicant_can_update_mobile_number_from_settings(): void
    {
        $applicant = User::factory()->create(['role' => 'user']);
        $applicant->profile()->create(['contact_number' => '09170000000']);

        $this->actingAs($applicant)
            ->get(route('settings.account'))
            ->assertOk()
            ->assertSee('id="mobile-number-settings"', false)
            ->assertSee('value="09170000000"', false);

        $this->put(route('settings.mobile-number.update'), [
            'mobile_number' => '+639171111111',
        ])->assertRedirect()
            ->assertSessionHas('settings_success', 'Your mobile number has been updated.');

        $this->assertDatabaseHas('users_profile', [
            'user_id' => $applicant->id,
            'contact_number' => '+639171111111',
        ]);
    }

    public function test_applicant_mobile_number_must_match_philippine_format(): void
    {
        $applicant = User::factory()->create(['role' => 'user']);

        $this->actingAs($applicant)
            ->put(route('settings.mobile-number.update'), [
                'mobile_number' => '12345',
            ])
            ->assertSessionHasErrors('mobile_number');
    }

    public function test_applicant_can_save_appearance_and_language_preferences(): void
    {
        $applicant = User::factory()->create(['role' => 'user']);

        $this->actingAs($applicant)
            ->put(route('settings.preferences.update'), [
                'appearance' => 'light',
                'language' => 'fil',
                'sidebar_behavior' => 'collapsed',
                'font_size' => 'large',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('applicant_settings', [
            'user_id' => $applicant->id,
            'appearance' => 'light',
            'theme_preference' => 'light',
            'language' => 'fil',
            'sidebar_behavior' => 'collapsed',
            'font_size' => 'large',
        ]);

        $this->get(route('settings.index'))
            ->assertOk()
            ->assertSee('<html lang="fil"', false)
            ->assertSee('Na-save na ang iyong mga kagustuhan.')
            ->assertSee('Mga Setting')
            ->assertSee('Itsura at Wika')
            ->assertSee('Wika')
            ->assertSee('Maliwanag')
            ->assertSee('Pangunahing Pahina')
            ->assertSee('data-theme="light"', false)
            ->assertSee('data-sidebar-behavior="collapsed"', false)
            ->assertSee('data-font-size="large"', false)
            ->assertSee('document.documentElement.lang = "fil-PH"', false)
            ->assertSee('document.documentElement.dataset.theme = "light"', false)
            ->assertSee('document.documentElement.dataset.sidebarBehavior = "collapsed"', false)
            ->assertSee('document.documentElement.dataset.fontSize = "large"', false)
            ->assertSee('for="appearance"', false)
            ->assertSee('for="sidebar_behavior"', false)
            ->assertSee('for="font_size"', false);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('document.documentElement.dataset.theme = "light"', false)
            ->assertSee('document.documentElement.dataset.sidebarBehavior = "collapsed"', false)
            ->assertSee('document.documentElement.dataset.fontSize = "large"', false);
    }

    public function test_applicant_dashboard_uses_the_saved_filipino_locale(): void
    {
        $applicant = User::factory()->create(['role' => 'user']);
        \App\Models\ApplicantSetting::create([
            'user_id' => $applicant->id,
            'language' => 'fil',
        ]);

        $this->actingAs($applicant)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<html lang="fil"', false)
            ->assertSee('Pangunahing Pahina')
            ->assertSee('Mga Anunsyo')
            ->assertSee('Maligayang pagbabalik,');

        $this->get(route('applications.create'))
            ->assertOk()
            ->assertSee('<html lang="fil"', false)
            ->assertSee('Impormasyon ng Pamilya at Pakikipag-ugnayan')
            ->assertSee('Sertipiko ng Kapanganakan');

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Mga Detalye ng Profile')
            ->assertSee('Kasalukuyang tirahan');

        $this->get(route('applicant.notifications.recent'))
            ->assertOk()
            ->assertSee('Mga Kamakailang Abiso');

        $this->get(route('applicant.notifications.previous'))
            ->assertOk()
            ->assertSee('Mga Nakaraang Abiso');

        $this->get(route('applicant.appointments.index'))
            ->assertOk()
            ->assertSee('Mga Takdang Oras')
            ->assertSee('Kalendaryo');

        $this->get(route('applicant.requirements'))
            ->assertOk()
            ->assertSee('Mga Karagdagang Kinakailangan');

        $this->put(route('settings.preferences.update'), ['language' => 'en'])
            ->assertRedirect();

        $this->get(route('settings.index'))
            ->assertOk()
            ->assertSee('<html lang="en"', false)
            ->assertSee('Appearance & Language')
            ->assertSee('Settings');
    }

    public function test_legacy_theme_preference_updates_the_portal_appearance(): void
    {
        $applicant = User::factory()->create(['role' => 'user']);

        $this->actingAs($applicant)
            ->put(route('settings.preferences.update'), [
                'theme_preference' => 'dark',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('applicant_settings', [
            'user_id' => $applicant->id,
            'appearance' => 'dark',
            'theme_preference' => 'dark',
        ]);

        $this->get(route('settings.index'))
            ->assertOk()
            ->assertSee('data-theme="dark"', false)
            ->assertSee('color:var(--primary)', false);
    }
}
