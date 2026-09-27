<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_user_profile_without_an_application(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['name' => 'Jordan Rivera']);

        $this->actingAs($admin)
            ->get(route('admin.users'))
            ->assertOk()
            ->assertSee(route('admin.users.show', $user), false)
            ->assertSee('data-user-view', false);

        $this->get(route('admin.users.show', ['user' => $user, 'search' => 'jordan']))
            ->assertOk()
            ->assertSee('Jordan Rivera')
            ->assertSee('No phone provided')
            ->assertSee('No address provided')
            ->assertSee('No information on file')
            ->assertSee(route('admin.users', ['search' => 'jordan']));
    }

    public function test_admin_user_profile_displays_existing_application_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['name' => 'Sam Applicant', 'last_active_at' => now()]);
        Application::factory()->for($user)->create(['status' => 'approved']);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Sam Applicant')
            ->assertSee('Application Approved')
            ->assertSee('Active');
    }

    public function test_grid_and_profile_use_profile_contact_fields_and_the_same_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $inactiveUser = User::factory()->create([
            'name' => 'Inactive Applicant',
            'last_active_at' => now()->subYears(2),
        ]);
        $inactiveUser->profile()->create([
            'contact_number' => null,
            'present_address' => null,
            'permanent_address' => 'Permanent address on file',
        ]);

        $pendingUser = User::factory()->create([
            'name' => 'Pending Applicant',
            'last_active_at' => now()->subYears(2),
        ]);
        Application::factory()->for($pendingUser)->create(['status' => 'pending']);

        $this->actingAs($admin)
            ->get(route('admin.users'))
            ->assertOk()
            ->assertSee('Inactive Applicant')
            ->assertSee('Inactive')
            ->assertSee('No phone provided')
            ->assertSee('Permanent address on file')
            ->assertSee('Pending Applicant')
            ->assertSee('Pending');

        $this->get(route('admin.users.show', $inactiveUser))
            ->assertOk()
            ->assertSee('Inactive')
            ->assertSee('Permanent address on file')
            ->assertSee('No phone provided');

        $this->get(route('admin.users.show', $pendingUser))
            ->assertOk()
            ->assertSee('Pending');
    }

    public function test_legacy_applicant_category_is_displayed_without_changing_its_value(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $user->profile()->create(['applicant_category' => 'OFW']);

        $this->actingAs($admin)
            ->get(route('admin.users'))
            ->assertOk()
            ->assertSee('OFW');

        $this->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('OFW');
    }

    public function test_applicant_profile_updates_are_visible_on_admin_user_profile(): void
    {
        $applicant = User::factory()->create(['name' => 'Old Applicant Name']);

        $this->actingAs($applicant)
            ->patch(route('profile.update'), [
                'first_name' => 'Jordan',
                'middle_name' => 'Lee',
                'last_name' => 'Rivera',
                'email' => $applicant->email,
                'sex' => 'Female',
                'date_of_birth' => '2000-01-01',
                'place_of_birth' => 'Lal-lo, Cagayan',
                'status' => 'Single',
                'citizenship' => 'Filipino',
                'social_media' => 'jordan.rivera',
                'gsis_beneficiary' => 'Mother',
                'contact_number' => '09171234567',
                'present_address' => 'Centro, Lal-lo',
                'permanent_address' => 'San Vicente, Lal-lo',
                'applicant_category' => 'student',
                'education_history' => [
                    ['level' => 'Elementary', 'school' => 'San Vicente Elementary', 'course' => '', 'year_level' => 'Grade 6', 'date_attended' => '2011-2017'],
                    ['level' => 'Secondary', 'school' => 'Lal-lo National High School', 'course' => 'General', 'year_level' => 'Grade 12', 'date_attended' => '2017-2023'],
                    ['level' => 'Tertiary', 'school' => 'Cagayan State University', 'course' => 'IT', 'year_level' => '2nd Year', 'date_attended' => '2023-present'],
                    ['level' => 'Tech-Voc', 'school' => '', 'course' => '', 'year_level' => '', 'date_attended' => ''],
                ],
                'father_name' => 'Alex Rivera',
                'father_contact_number' => '09170000001',
                'father_occupation' => 'Driver',
                'mother_name' => 'Maria Rivera',
                'mother_contact_number' => '09170000002',
                'mother_occupation' => 'Teacher',
                'parent_status_details' => ['Living Together', 'Guardian'],
                'special_skills' => 'Communication, teamwork',
            ])
            ->assertRedirect(route('profile.edit'));

        $applicant->refresh();
        $this->assertSame('Jordan Lee Rivera', $applicant->name);
        $this->assertDatabaseHas('users_profile', [
            'user_id' => $applicant->id,
            'first_name' => 'Jordan',
            'middle_name' => 'Lee',
            'last_name' => 'Rivera',
            'place_of_birth' => 'Lal-lo, Cagayan',
            'contact_number' => '09171234567',
            'present_address' => 'Centro, Lal-lo',
            'permanent_address' => 'San Vicente, Lal-lo',
            'father_name' => 'Alex Rivera',
            'mother_name' => 'Maria Rivera',
            'special_skills' => 'Communication, teamwork',
        ]);
        $savedParentStatuses = $applicant->profile->parent_status_details;
        $this->assertSame([
            'Living Together' => true,
            'Solo Parent' => false,
            'Orphan' => false,
            'Guardian' => true,
        ], $savedParentStatuses);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('admin.users.show', $applicant))
            ->assertOk()
            ->assertSee('Jordan Lee Rivera')
            ->assertSee('Female')
            ->assertSee('Jan 01, 2000 (' . \Illuminate\Support\Carbon::parse('2000-01-01')->age . ' years old)')
            ->assertSee('Lal-lo, Cagayan')
            ->assertSee('Single')
            ->assertSee('Filipino')
            ->assertSee('Centro, Lal-lo')
            ->assertSee('San Vicente, Lal-lo')
            ->assertSee('jordan.rivera')
            ->assertSee('Mother')
            ->assertSee('09171234567')
            ->assertSee('Student')
            ->assertSee('Education Background')
            ->assertSee('San Vicente Elementary')
            ->assertSee('Cagayan State University')
            ->assertDontSee('Tech-Voc')
            ->assertSee('Parents Information')
            ->assertSee('Alex Rivera')
            ->assertSee('09170000001')
            ->assertSee('Driver')
            ->assertSee('Maria Rivera')
            ->assertSee('Teacher')
            ->assertSee('Current Status of Parents')
            ->assertSee('Living Together')
            ->assertSee('Guardian')
            ->assertSee('Special Skills')
            ->assertSee('Communication, teamwork');

        $this->get(route('admin.users'))
            ->assertOk()
            ->assertSee('09171234567')
            ->assertSee('Centro, Lal-lo')
            ->assertDontSee('No phone provided')
            ->assertDontSee('No address provided');
    }

    public function test_profile_update_rejects_legacy_applicant_category_values(): void
    {
        $applicant = User::factory()->create();

        $this->actingAs($applicant)
            ->patch(route('profile.update'), [
                'email' => $applicant->email,
                'applicant_category' => 'OFW',
            ])
            ->assertSessionHasErrors('applicant_category');
    }
}