<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Application;
use App\Models\ApplicantSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_publish_a_shared_appointment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.appointments.index'))
            ->assertOk()
            ->assertSee('Shared Schedule');

        $this->get(route('admin.appointments.create'))
            ->assertOk()
            ->assertSee('Appointment details')
            ->assertSee('Who should receive this appointment?')
            ->assertSee('Pending — not yet approved')
            ->assertSee('One specific applicant')
            ->assertSee('Multiple specific applicants')
            ->assertSee('Search by name')
            ->assertSee('role="combobox"', false)
            ->assertSee('role="listbox"', false)
            ->assertSee('Select all applicants')
            ->assertSee('Clear selection')
            ->assertSee('.applicant-picker-option[hidden] { display: none; }', false)
            ->assertSee('data-admin-confirm-backdrop', false)
            ->assertSee('Leave without saving?', false)
            ->assertSee('beforeunload', false)
            ->assertSee('data-name="'.$applicant->name.'"', false);

        $this->post(route('admin.appointments.store'), [
            'title' => 'Application orientation',
            'description' => 'Bring your application documents.',
            'location' => 'PESO Office',
            'starts_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
        ])
        ->assertRedirect(route('admin.appointments.index'));

        $appointment = Appointment::firstOrFail();
        $this->assertFalse($appointment->is_published);
        $this->assertSame($admin->id, $appointment->created_by);

        $this->post(route('admin.appointments.toggle', $appointment))
            ->assertRedirect(route('admin.appointments.index'));

        $this->assertTrue($appointment->fresh()->is_published);

        $this->actingAs($applicant)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Upcoming Appointments')
            ->assertSee('Application orientation')
            ->assertSee('PESO Office')
            ->assertSee('Appointment reminder')
            ->assertSee('Mark as read');

        $this->get(route('applicant.notifications.recent'))
            ->assertOk()
            ->assertSee('Appointment reminder')
            ->assertSee('Appointment date')
            ->assertSee('PESO Office');
    }

    public function test_email_preference_still_saves_appointment_notification_for_recent_feed_and_dashboard_popup(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create();
        ApplicantSetting::create([
            'user_id' => $applicant->id,
            'notification_method' => 'email',
        ]);
        $startsAt = now()->addDays(4)->setTime(10, 30);
        $appointment = Appointment::create([
            'title' => 'Individual appointment',
            'location' => 'PESO Office',
            'starts_at' => $startsAt,
            'target_audience' => 'specific_applicant',
            'target_user_id' => $applicant->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.appointments.toggle', $appointment))
            ->assertRedirect(route('admin.appointments.index'));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $applicant->id,
            'type' => \App\Notifications\ApplicantPortalUpdate::class,
        ]);

        $this->actingAs($applicant)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Individual appointment')
            ->assertSee($startsAt->format('M j, Y · g:i A'));

        $this->get(route('applicant.notifications.recent'))
            ->assertOk()
            ->assertSee($startsAt->format('l, F j, Y · g:i A'))
            ->assertSee('PESO Office');
    }

    public function test_dashboard_shows_a_centered_popup_for_unread_appointment_notifications(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 10)->setTime(9, 0));
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create();
        $appointment = Appointment::create([
            'title' => 'Document verification',
            'description' => 'Bring the original documents.',
            'location' => 'PESO Lal-lo',
            'starts_at' => now()->setTime(14, 30),
            'is_published' => true,
            'target_audience' => 'all_applicants',
            'created_by' => $admin->id,
        ]);
        $applicant->notify(new \App\Notifications\ApplicantPortalUpdate(
            'notify_appointments',
            'Appointment reminder',
            'Your appointment is today. Bring the original documents.',
            [
                'appointment_id' => $appointment->id,
                'appointment_date' => $appointment->starts_at->toIso8601String(),
                'appointment_location' => $appointment->location,
            ],
        ));
        Appointment::create([
            'title' => 'Tomorrow appointment',
            'starts_at' => now()->addDay(),
            'is_published' => true,
            'target_audience' => 'all_applicants',
            'created_by' => $admin->id,
        ]);
        Appointment::create([
            'title' => 'Draft appointment today',
            'starts_at' => now()->setTime(15, 0),
            'is_published' => false,
            'target_audience' => 'all_applicants',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($applicant)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-appointment-day-popup', false)
            ->assertSee('Appointment reminders')
            ->assertSee('Appointment reminder')
            ->assertSee('Bring the original documents.')
            ->assertSee('PESO Lal-lo')
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('Mark as read')
            ->assertSee('This reminder will close in 10 seconds.')
            ->assertSee('data-appointment-day-read', false)
            ->assertDontSee('data-appointment-day-read disabled', false)
            ->assertSee(route('notifications.read', $applicant->notifications()->firstOrFail()->id), false)
            ->assertSee(route('applicant.appointments.index'), false)
            ->assertDontSee('Draft appointment today');
    }

    public function test_applicant_can_mark_an_appointment_notification_as_read_to_close_the_popup(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 10)->setTime(9, 0));
        $applicant = User::factory()->create();
        $appointment = Appointment::create([
            'title' => 'Document verification',
            'description' => 'Bring your original documents.',
            'starts_at' => now()->setTime(14, 30),
            'is_published' => true,
            'target_audience' => 'all_applicants',
        ]);
        $applicant->notify(new \App\Notifications\ApplicantPortalUpdate(
            'notify_appointments',
            'Appointment today: Document verification',
            'Your appointment is today.',
            [
                'appointment_id' => $appointment->id,
                'appointment_title' => $appointment->title,
                'appointment_description' => $appointment->description,
                'appointment_date' => $appointment->starts_at->format('M j, Y g:i A'),
                'appointment_location' => $appointment->location,
                'event' => 'appointment_day_of',
            ],
        ));
        $dayOfNotification = $applicant->notifications()->firstOrFail();
        $applicant->notify(new \App\Notifications\ApplicantPortalUpdate(
            'notify_documents',
            'Application update',
            'This notification must not be changed.',
        ));
        $unrelatedNotification = $applicant->notifications()
            ->whereJsonContains('data->title', 'Application update')
            ->firstOrFail();

        $this->actingAs($applicant)
            ->from(route('dashboard'))
            ->post(route('notifications.read', $dayOfNotification->id), ['redirect_to' => 'back'])
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($dayOfNotification->fresh()->read_at);
        $this->assertNull($unrelatedNotification->fresh()->read_at);
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-appointment-day-popup', false);
    }

    public function test_picker_includes_all_user_role_accounts_but_not_admin_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicants = User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.appointments.create'))
            ->assertOk();

        foreach ($applicants as $applicant) {
            $response->assertSee('data-id="'.$applicant->id.'"', false);
        }
        $response->assertDontSee('data-id="'.$admin->id.'"', false);
    }

    public function test_admin_must_provide_an_attendance_date_and_time(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.appointments.store'), [
                'title' => 'Application orientation',
            ])
            ->assertSessionHasErrors('starts_at');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_admin_can_create_an_appointment_for_one_specific_applicant(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.appointments.store'), [
                'title' => 'Individual document review',
                'starts_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
                'target_audience' => 'specific_applicant',
                'target_user_id' => $applicant->id,
            ])
            ->assertRedirect(route('admin.appointments.index'));

        $appointment = Appointment::firstOrFail();
        $this->assertSame('specific_applicant', $appointment->target_audience);
        $this->assertSame($applicant->id, $appointment->target_user_id);

        $this->get(route('admin.appointments.index'))
            ->assertOk()
            ->assertSee($applicant->name);
    }

    public function test_admin_must_select_an_applicant_for_an_individual_appointment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.appointments.store'), [
                'title' => 'Individual document review',
                'starts_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
                'target_audience' => 'specific_applicant',
            ])
            ->assertSessionHasErrors('target_user_id');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_admin_can_target_multiple_selected_applicants(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $firstApplicant = User::factory()->create();
        $secondApplicant = User::factory()->create();
        $otherApplicant = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.appointments.store'), [
                'title' => 'Small group orientation',
                'starts_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
                'target_audience' => 'multiple_applicants',
                'target_user_ids' => [$firstApplicant->id, $secondApplicant->id],
            ])
            ->assertRedirect(route('admin.appointments.index'));

        $appointment = Appointment::firstOrFail();
        $this->assertSame('multiple_applicants', $appointment->target_audience);
        $this->assertEqualsCanonicalizing(
            [$firstApplicant->id, $secondApplicant->id],
            $appointment->targetApplicants()->pluck('users.id')->all(),
        );

        $this->get(route('admin.appointments.edit', $appointment))
            ->assertOk()
            ->assertSee('multiple_applicants')
            ->assertSee('Select at least two applicants');
        $this->get(route('admin.appointments.index'))
            ->assertOk()
            ->assertSee('2 selected applicants');

        $this->post(route('admin.appointments.toggle', $appointment))
            ->assertRedirect(route('admin.appointments.index'));

        foreach ([$firstApplicant, $secondApplicant] as $recipient) {
            $this->assertDatabaseHas('notifications', [
                'notifiable_id' => $recipient->id,
                'type' => \App\Notifications\ApplicantPortalUpdate::class,
            ]);
        }
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $otherApplicant->id,
            'type' => \App\Notifications\ApplicantPortalUpdate::class,
        ]);

        foreach ([$firstApplicant, $secondApplicant] as $recipient) {
            $this->actingAs($recipient)
                ->get(route('applicant.appointments.index'))
                ->assertOk()
                ->assertSee('Small group orientation');
        }
        $this->actingAs($otherApplicant)
            ->get(route('applicant.appointments.index'))
            ->assertOk()
            ->assertDontSee('Small group orientation');
    }

    public function test_multiple_applicant_appointment_requires_at_least_two_valid_applicants(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create();
        $adminUser = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.appointments.store'), [
                'title' => 'Small group orientation',
                'starts_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
                'target_audience' => 'multiple_applicants',
                'target_user_ids' => [$applicant->id],
            ])
            ->assertSessionHasErrors('target_user_ids');

        $this->post(route('admin.appointments.store'), [
            'title' => 'Small group orientation',
            'starts_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'target_audience' => 'multiple_applicants',
            'target_user_ids' => [$applicant->id, $adminUser->id],
        ])->assertSessionHasErrors('target_user_ids.1');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_applicants_only_see_appointments_targeted_to_their_latest_status_or_them_individually(): void
    {
        $pendingApplicant = User::factory()->create();
        Application::factory()->for($pendingApplicant)->create(['status' => 'approved']);
        Application::factory()->for($pendingApplicant)->create(['status' => 'pending']);

        $approvedApplicant = User::factory()->create();
        Application::factory()->for($approvedApplicant)->create(['status' => 'approved']);

        $deniedApplicant = User::factory()->create();
        Application::factory()->for($deniedApplicant)->create(['status' => 'denied']);

        $noApplicationApplicant = User::factory()->create();
        $shared = [
            'starts_at' => now()->addDays(5),
            'is_published' => true,
        ];

        Appointment::create($shared + [
            'title' => 'Pending applicant appointment',
            'target_audience' => 'pending_applicants',
        ]);
        Appointment::create($shared + [
            'title' => 'Approved applicant appointment',
            'target_audience' => 'approved_applicants',
        ]);
        Appointment::create($shared + [
            'title' => 'Denied applicant appointment',
            'target_audience' => 'denied_applicants',
        ]);
        Appointment::create($shared + [
            'title' => 'Only for one applicant',
            'target_audience' => 'specific_applicant',
            'target_user_id' => $noApplicationApplicant->id,
        ]);
        Appointment::create($shared + [
            'title' => 'Shared appointment',
            'target_audience' => 'all_applicants',
        ]);

        $this->actingAs($pendingApplicant)
            ->get(route('applicant.appointments.index'))
            ->assertOk()
            ->assertSee('Pending applicant appointment')
            ->assertSee('Shared appointment')
            ->assertDontSee('Approved applicant appointment')
            ->assertDontSee('Denied applicant appointment')
            ->assertDontSee('Only for one applicant');

        $this->actingAs($approvedApplicant)
            ->get(route('applicant.appointments.index'))
            ->assertOk()
            ->assertSee('Approved applicant appointment')
            ->assertSee('Shared appointment')
            ->assertDontSee('Pending applicant appointment')
            ->assertDontSee('Denied applicant appointment')
            ->assertDontSee('Only for one applicant');

        $this->actingAs($deniedApplicant)
            ->get(route('applicant.appointments.index'))
            ->assertOk()
            ->assertSee('Denied applicant appointment')
            ->assertSee('Shared appointment')
            ->assertDontSee('Pending applicant appointment')
            ->assertDontSee('Approved applicant appointment')
            ->assertDontSee('Only for one applicant');

        $this->actingAs($noApplicationApplicant)
            ->get(route('applicant.appointments.index'))
            ->assertOk()
            ->assertSee('Only for one applicant')
            ->assertSee('Shared appointment')
            ->assertDontSee('Pending applicant appointment')
            ->assertDontSee('Approved applicant appointment')
            ->assertDontSee('Denied applicant appointment');
    }

    public function test_publishing_a_status_targeted_appointment_notifies_only_matching_applicants(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pendingApplicant = User::factory()->create();
        Application::factory()->for($pendingApplicant)->create(['status' => 'pending']);
        $approvedApplicant = User::factory()->create();
        Application::factory()->for($approvedApplicant)->create(['status' => 'approved']);
        $appointment = Appointment::create([
            'title' => 'Pending application review',
            'starts_at' => now()->addDays(3),
            'target_audience' => 'pending_applicants',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.appointments.toggle', $appointment))
            ->assertRedirect(route('admin.appointments.index'));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $pendingApplicant->id,
            'type' => \App\Notifications\ApplicantPortalUpdate::class,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $approvedApplicant->id,
            'type' => \App\Notifications\ApplicantPortalUpdate::class,
        ]);
    }

    public function test_applicant_cannot_access_admin_appointment_management(): void
    {
        $applicant = User::factory()->create();

        $this->actingAs($applicant)
            ->get(route('admin.appointments.index'))
            ->assertForbidden();
    }

    public function test_announcement_sidebar_dropdown_contains_appointments_news_and_schedule(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        foreach ([
            'admin.appointments.index',
            'admin.news.index',
            'admin.settings',
        ] as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee('Announcements')
                ->assertSee('aria-expanded="true"', false)
                ->assertSee(route('admin.appointments.index'), false)
                ->assertSee(route('admin.news.index'), false)
                ->assertSee(route('admin.settings'), false);
        }
    }

    public function test_applicant_dashboard_hides_drafts_and_past_appointments(): void
    {
        $applicant = User::factory()->create();
        Appointment::create([
            'title' => 'Draft schedule',
            'starts_at' => now()->addDays(2),
            'is_published' => false,
        ]);
        Appointment::create([
            'title' => 'Past published schedule',
            'starts_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $this->actingAs($applicant)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Draft schedule')
            ->assertDontSee('Past published schedule');
    }

    public function test_applicant_sees_calendar_and_upcoming_appointments_together_on_one_page(): void
    {
        $applicant = User::factory()->create();
        Appointment::create([
            'title' => 'Document Verification',
            'description' => 'Bring your original documents.',
            'location' => 'PESO Office, Lal-lo',
            'starts_at' => now()->addDays(5)->setTime(9, 0),
            'is_published' => true,
        ]);
        Appointment::create([
            'title' => 'Unpublished draft appointment',
            'starts_at' => now()->addDays(6),
            'is_published' => false,
        ]);

        $this->actingAs($applicant)
            ->get(route('applicant.appointments.index'))
            ->assertOk()
            ->assertSee('Appointments')
            ->assertSee('Calendar')
            ->assertSee('Upcoming List')
            ->assertSee('Document Verification')
            ->assertSee('Upcoming List')
            ->assertSee('Next up')
            ->assertSee('Published appointments scheduled from today onward')
            ->assertSee('Bring your original documents.')
            ->assertSee('aria-expanded="true"', false)
            ->assertSee('href="' . route('applicant.appointments.index') . '" class="nav-link active"', false)
            ->assertDontSee('Unpublished draft appointment');
    }

    public function test_upcoming_list_shows_all_published_future_appointments_across_months(): void
    {
        $applicant = User::factory()->create();

        foreach ([
            ['title' => 'Next month appointment', 'starts_at' => now()->addMonth()],
            ['title' => 'Next year appointment', 'starts_at' => now()->addYear()],
            ['title' => 'Far future appointment', 'starts_at' => now()->addYears(2)],
        ] as $appointment) {
            Appointment::create($appointment + ['is_published' => true]);
        }

        Appointment::create([
            'title' => 'Unpublished future appointment',
            'starts_at' => now()->addYears(3),
            'is_published' => false,
        ]);

        Appointment::create([
            'title' => 'Past appointment',
            'starts_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $response = $this->actingAs($applicant)
            ->get(route('applicant.appointments.index', ['month' => now()->addMonths(6)->format('Y-m')]))
            ->assertOk()
            ->assertSee('Next month appointment')
            ->assertSee('Next year appointment')
            ->assertSee('Far future appointment')
            ->assertSee('3 upcoming appointments')
            ->assertSee('Next up')
            ->assertDontSee('Unpublished future appointment')
            ->assertDontSee('Past appointment')
            ->assertSee('classList.toggle(\'is-list-view\', upcomingSelected)', false);

        $this->assertSame(3, substr_count($response->getContent(), 'class="upcoming-item"'));
    }

    public function test_appointment_page_rejects_invalid_calendar_filters(): void
    {
        $applicant = User::factory()->create();

        $this->actingAs($applicant)
            ->get(route('applicant.appointments.index', ['month' => '2026-13']))
            ->assertBadRequest();
    }
}
