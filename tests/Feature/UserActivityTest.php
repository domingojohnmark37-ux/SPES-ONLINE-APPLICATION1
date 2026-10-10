<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_applicant_user_guide_explains_portal_features_and_is_not_available_to_admins(): void
    {
        $applicant = User::factory()->create(['role' => 'user']);

        $this->actingAs($applicant)
            ->get(route('applicant.manual'))
            ->assertOk()
            ->assertSee('SPES Portal User Guide')
            ->assertSee('How to Apply')
            ->assertSee('Complete the application form')
            ->assertSee('Each file must be 5 MB or smaller.')
            ->assertSee('Submit for PESO review')
            ->assertSee('Track your application')
            ->assertSee('Complete the next steps if approved')
            ->assertSee('Dashboard')
            ->assertSee('My Application')
            ->assertSee('Additional Requirements')
            ->assertSee('Notifications and Appointments')
            ->assertSee('Help and Contact')
            ->assertSee('Profile and Settings')
            ->assertSee('Keep Your Account Safe')
            ->assertSee('data-open-portal-help', false);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('applicant.manual'))
            ->assertForbidden();
    }

    public function test_successful_login_records_last_activity(): void
    {
        $user = User::factory()->create();

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36')
            ->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->fresh()->last_active_at);
        $this->assertSame('Active', $user->fresh()->activityStatus());
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => \App\Notifications\ApplicantLoginAlert::class,
        ]);

        $this->actingAs($user)
            ->get(route('applicant.notifications.previous'))
            ->assertOk()
            ->assertSee('New sign-in to your SPES account')
            ->assertSee('Google Chrome on Windows (Desktop)')
            ->assertSee('Device: Google Chrome on Windows (Desktop)');
    }

    public function test_authenticated_request_and_logout_refresh_last_activity(): void
    {
        $user = User::factory()->create(['last_active_at' => now()->subDays(3)]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->assertTrue($user->fresh()->last_active_at->greaterThan(now()->subMinute()));

        $this->post(route('logout'))->assertRedirect('/');
        $this->assertTrue($user->fresh()->last_active_at->greaterThan(now()->subMinute()));
    }

    public function test_activity_status_is_inactive_after_one_year(): void
    {
        $user = User::factory()->create(['last_active_at' => now()->subYears(2)]);

        $this->assertSame('Inactive', $user->activityStatus());
        $this->assertStringStartsWith('Logged out ', $user->activityDescription());
    }

    public function test_pending_application_status_remains_pending_for_inactive_users(): void
    {
        $user = User::factory()->create(['last_active_at' => now()->subYears(2)]);

        $this->assertSame('Pending', $user->displayStatus('pending'));
        $this->assertSame('Inactive', $user->displayStatus('denied'));
    }

    public function test_applicant_dashboard_shows_grouped_quick_reply_help_without_text_entry(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Application Timeline')
            ->assertSee('Application Status')
            ->assertSee('Required Documents')
            ->assertSee('Quick Actions')
            ->assertSee('Announcements')
            ->assertSee('Appointments')
            ->assertSee('Notifications')
            ->assertSee('FAQs')
            ->assertSee('Profile')
            ->assertDontSee('Account Settings')
            ->assertSee('>FAQs</span>', false)
            ->assertDontSee('Need Help? Chat with Us')
            ->assertSee('Upcoming Appointments')
            ->assertSee('Profile Completion')
            ->assertSee('Recent Notifications')
            ->assertSee('SPES Applicant Help')
            ->assertSee('data-nav-group-toggle', false)
            ->assertSee('aria-controls="applicant-application-submenu"', false)
            ->assertSee('id="applicant-application-submenu"', false)
            ->assertSee('aria-controls="applicant-notifications-submenu"', false)
            ->assertSee('id="applicant-notifications-submenu"', false)
            ->assertSee('href="' . route('applicant.appointments.index') . '"', false)
            ->assertSee('href="' . route('applicant.notifications.recent') . '"', false)
            ->assertSee('href="' . route('applicant.notifications.previous') . '"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('aria-controls="applicant-support-submenu"', false)
            ->assertSee('id="applicant-support-submenu"', false)
            ->assertSee('Need Help &amp; Support', false)
            ->assertSee('Previous Notifications')
            ->assertSee('Apply Now')
            ->assertSee('Application')
            ->assertSee('Documents')
            ->assertSee('After approval')
            ->assertSee('How do I apply?')
            ->assertSee('data-answer=', false)
            ->assertDontSee('id="portalHelpInput"', false)
            ->assertSeeInOrder([
                'Dashboard',
                'My Application',
                'Application Status',
                'Apply Now',
                'Requirements',
                'Notifications',
                'Appointments',
                'Recent Notifications',
                'Previous Notifications',
                'Need Help & Support',
                'FAQs',
                'Contact Us',
                'Profile',
            ], false);
    }

    public function test_dashboard_notification_dropdown_uses_a_scrollable_wrapping_list(): void
    {
        $user = User::factory()->create();
        $longMessage = str_repeat('SPESnotificationwithoutspaces', 12);

        foreach (range(1, 12) as $index) {
            $user->notify(new \App\Notifications\ApplicantPortalUpdate(
                'notify_documents',
                "Portal notification {$index}",
                $index === 1 ? $longMessage : "Notification message {$index}",
            ));
        }

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('class="notif-list"', false)
            ->assertSee('aria-label="Unread notifications"', false)
            ->assertSee('overflow-y:auto', false)
            ->assertSee('overflow-wrap:anywhere', false)
            ->assertSee($longMessage)
            ->assertSee('class="notif-footer"', false)
            ->assertSee('Mark all as read');
    }

    public function test_recent_notifications_has_its_own_page_with_published_admin_announcements(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->travelTo(now()->setDate(2026, 8, 23)->setTime(10, 24));
        News::create([
            'title' => 'SPES Orientation Announcement',
            'content' => 'Orientation will be held at PESO Lal-lo.',
            'display_on' => 'portal',
            'is_published' => true,
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);
        News::create([
            'title' => 'Unpublished draft announcement',
            'content' => 'This draft must not be shown.',
            'display_on' => 'portal',
            'is_published' => false,
            'created_by' => $admin->id,
        ]);
        News::create([
            'title' => 'Landing page only announcement',
            'content' => 'This is not for portal applicants.',
            'display_on' => 'landing',
            'is_published' => true,
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        $this->actingAs($user)
            ->get(route('applicant.notifications.recent'))
            ->assertOk()
            ->assertSee('Recent Notifications')
            ->assertSee('aria-expanded="true"', false)
            ->assertSee('href="' . route('applicant.notifications.recent') . '" class="nav-link active"', false)
            ->assertSee('Unread notifications and items received in the last 24 hours.')
            ->assertSee('SPES Orientation Announcement')
            ->assertSee('Orientation will be held at PESO Lal-lo.')
            ->assertSee('Aug 23, 2026 · 10:24 AM')
            ->assertDontSee('Unpublished draft announcement')
            ->assertDontSee('Landing page only announcement')
            ->assertDontSee('Requirements Verified');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="' . route('applicant.notifications.recent') . '"', false)
            ->assertSee('href="' . route('applicant.notifications.previous') . '"', false)
            ->assertDontSee('id="notifications"', false);
    }

    public function test_recent_notifications_show_unread_items_and_items_from_the_last_24_hours(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $now = now();
        $this->travelTo($now);

        collect([
            ['title' => 'Old unread update', 'created_at' => $now->copy()->subDays(7), 'read_at' => null],
            ['title' => 'Recent read update', 'created_at' => $now->copy()->subHours(23), 'read_at' => $now->copy()->subHours(22)],
            ['title' => 'Old read update', 'created_at' => $now->copy()->subHours(25), 'read_at' => $now->copy()->subHours(24)],
        ])->each(function (array $attributes) use ($user) {
            $user->notify(new \App\Notifications\ApplicantPortalUpdate(
                'notify_documents',
                $attributes['title'],
                'Notification test message.',
                ['status' => 'approved'],
            ));

            $notification = $user->notifications()
                ->whereJsonContains('data->title', $attributes['title'])
                ->firstOrFail();
            \Illuminate\Support\Facades\DB::table('notifications')
                ->where('id', $notification->id)
                ->update([
                    'created_at' => $attributes['created_at'],
                    'read_at' => $attributes['read_at'],
                ]);

        });

        News::create([
            'title' => 'Recent portal announcement',
            'content' => 'Published within the last 24 hours.',
            'display_on' => 'portal',
            'is_published' => true,
            'published_at' => $now->copy()->subHours(23),
            'created_by' => $admin->id,
        ]);
        News::create([
            'title' => 'Old portal announcement',
            'content' => 'Published more than 24 hours ago.',
            'display_on' => 'portal',
            'is_published' => true,
            'published_at' => $now->copy()->subHours(25),
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($user)->get(route('applicant.notifications.recent'));
        $response->assertOk()
            ->assertSee('Old unread update')
            ->assertSee('Recent read update')
            ->assertDontSee('Old read update')
            ->assertSee('Recent portal announcement')
            ->assertDontSee('Old portal announcement');
    }

    public function test_previous_notifications_archived_filter_shows_read_recent_items_after_24_hours(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $now = now();
        $this->travelTo($now);

        foreach ([
            ['title' => 'Archived read application update', 'created_at' => $now->copy()->subHours(25), 'read_at' => $now->copy()->subHours(24)],
            ['title' => 'Unread application update', 'created_at' => $now->copy()->subDays(2), 'read_at' => null],
            ['title' => 'Recently read application update', 'created_at' => $now->copy()->subHours(12), 'read_at' => $now->copy()->subHours(11)],
        ] as $attributes) {
            $user->notify(new \App\Notifications\ApplicantPortalUpdate(
                'notify_documents',
                $attributes['title'],
                'Notification archive test.',
                ['status' => 'approved'],
            ));

            $notification = $user->notifications()
                ->whereJsonContains('data->title', $attributes['title'])
                ->firstOrFail();
            \Illuminate\Support\Facades\DB::table('notifications')
                ->where('id', $notification->id)
                ->update([
                    'created_at' => $attributes['created_at'],
                    'read_at' => $attributes['read_at'],
                ]);
        }

        News::create([
            'title' => 'Archived portal announcement',
            'content' => 'This announcement has expired from the recent list.',
            'display_on' => 'portal',
            'is_published' => true,
            'published_at' => $now->copy()->subHours(25),
            'created_by' => $admin->id,
        ]);

        $this->actingAs($user)
            ->get(route('applicant.notifications.previous', ['filter' => 'archived']))
            ->assertOk()
            ->assertSee('Archived read application update')
            ->assertSee('Archived portal announcement')
            ->assertSee('Archived from Recent Notifications')
            ->assertDontSee('Unread application update')
            ->assertDontSee('Recently read application update');
    }

    public function test_unread_notification_in_previous_history_can_be_marked_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new \App\Notifications\ApplicantPortalUpdate(
            'notify_documents',
            'Application approved',
            'Your application has been approved.',
            ['status' => 'approved'],
        ));
        $notification = $user->notifications()->firstOrFail();
        $historyUrl = route('applicant.notifications.previous', ['filter' => 'all', 'search' => 'Application']);

        $this->actingAs($user)
            ->get($historyUrl)
            ->assertOk()
            ->assertSee('Mark as read')
            ->assertSee(route('notifications.read', $notification->id), false);

        $this->from($historyUrl)
            ->post(route('notifications.read', $notification->id), ['redirect_to' => 'back'])
            ->assertRedirect($historyUrl);

        $this->assertNotNull($notification->fresh()->read_at);

        $this->get($historyUrl)
            ->assertOk()
            ->assertSee('Read ')
            ->assertDontSee('Mark as read');
    }

    public function test_previous_notification_history_wraps_long_content_and_groups_recent_items(): void
    {
        $user = User::factory()->create();
        $longMessage = str_repeat('SPES-notification-message-without-spaces-', 8);
        $user->notify(new \App\Notifications\ApplicantPortalUpdate(
            'notify_documents',
            str_repeat('Long notification title without spaces ', 8),
            $longMessage,
        ));

        $this->actingAs($user)
            ->get(route('applicant.notifications.previous'))
            ->assertOk()
            ->assertSee('class="history-scroll"', false)
            ->assertSee('class="history-group-title">Today</h2>', false)
            ->assertSee('overflow-wrap:anywhere', false)
            ->assertSee('View full message')
            ->assertSee($longMessage)
            ->assertSee('Mark all as read')
            ->assertSee('Dismiss');
    }

    public function test_previous_notification_actions_update_only_the_own_notification(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $user->notify(new \App\Notifications\ApplicantPortalUpdate(
            'notify_documents',
            'Manage my notification',
            'This notification belongs to the current user.',
        ));
        $otherUser->notify(new \App\Notifications\ApplicantPortalUpdate(
            'notify_documents',
            'Other user notification',
            'This notification belongs to someone else.',
        ));
        $notification = $user->notifications()->firstOrFail();
        $otherNotification = $otherUser->notifications()->firstOrFail();

        $this->actingAs($user)
            ->post(route('notifications.read', $notification->id))
            ->assertOk()
            ->assertJson(['ok' => true]);
        $this->assertNotNull($notification->fresh()->read_at);

        $this->post(route('notifications.unread', $otherNotification->id))
            ->assertNotFound();
        $this->assertNull($otherNotification->fresh()->read_at);

        $this->post(route('notifications.unread', $notification->id))
            ->assertRedirect();
        $this->assertNull($notification->fresh()->read_at);

        $this->delete(route('notifications.dismiss', $otherNotification->id))
            ->assertNotFound();
        $this->assertDatabaseHas('notifications', ['id' => $otherNotification->id]);

        $this->deleteJson(route('notifications.dismiss', $notification->id))
            ->assertOk()
            ->assertJson(['ok' => true]);
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);

        $user->notify(new \App\Notifications\ApplicantPortalUpdate(
            'notify_documents',
            'Dismiss with redirect',
            'The standard form submission remains supported.',
        ));
        $redirectNotification = $user->notifications()->firstOrFail();

        $this->from(route('applicant.notifications.previous'))
            ->delete(route('notifications.dismiss', $redirectNotification->id))
            ->assertRedirect(route('applicant.notifications.previous'));
        $this->assertDatabaseMissing('notifications', ['id' => $redirectNotification->id]);
    }

    public function test_previous_notification_history_can_mark_all_unread_items_as_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new \App\Notifications\ApplicantPortalUpdate(
            'notify_documents',
            'First unread notification',
            'First message.',
        ));
        $user->notify(new \App\Notifications\ApplicantPortalUpdate(
            'notify_documents',
            'Second unread notification',
            'Second message.',
        ));

        $this->actingAs($user)
            ->from(route('applicant.notifications.previous'))
            ->post(route('notifications.readAll'), ['redirect_to' => 'back'])
            ->assertRedirect(route('applicant.notifications.previous'))
            ->assertSessionHas('status');

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_previous_notifications_has_its_own_filterable_history_page(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->travelTo(now()->setDate(2026, 8, 23)->setTime(10, 24));

        $user->notify(new \App\Notifications\ApplicantPortalUpdate(
            'notify_documents',
            'Document requirement update',
            'Please review your submitted documents.',
        ));
        $user->notify(new \App\Notifications\ApplicantPortalUpdate(
            'notify_documents',
            'Application approved',
            'Your SPES application has been approved.',
            ['status' => 'approved'],
        ));
        News::create([
            'title' => 'Important Announcement',
            'content' => 'The SPES application period is now open.',
            'display_on' => 'portal',
            'is_published' => true,
            'published_at' => now()->subDays(2),
            'created_by' => $admin->id,
        ]);
        \App\Models\Appointment::create([
            'title' => 'SPES Orientation Program',
            'description' => 'Orientation for qualified applicants.',
            'location' => 'PESO Office, Lal-lo',
            'starts_at' => now()->subDays(8),
            'is_published' => true,
        ]);

        $this->actingAs($user)
            ->get(route('applicant.notifications.previous'))
            ->assertOk()
            ->assertSee('Previous Notifications')
            ->assertSee('Read notifications stay in Recent for 24 hours, then appear in the archive. Unread notifications remain in Recent.')
            ->assertSee('Document requirement update')
            ->assertSee('Important Announcement')
            ->assertSee('SPES Orientation Program')
            ->assertSee('Account Created')
            ->assertSee('Application approved')
            ->assertSee('Unread — delivered in-app')
            ->assertSee('Events')
            ->assertSee('Announcements')
            ->assertSee('System')
            ->assertSee('aria-expanded="true"', false)
            ->assertSee('href="' . route('applicant.notifications.previous') . '" class="nav-link active"', false);

        $this->get(route('applicant.notifications.previous', ['filter' => 'events', 'search' => 'Orientation']))
            ->assertOk()
            ->assertSee('SPES Orientation Program')
            ->assertDontSee('Important Announcement');

        $this->get(route('applicant.notifications.previous', ['filter' => 'announcements']))
            ->assertOk()
            ->assertSee('Important Announcement')
            ->assertDontSee('SPES Orientation Program');

        $this->get(route('applicant.notifications.previous', ['filter' => 'invalid']))
            ->assertBadRequest();
    }

    public function test_application_status_notifications_are_recent_until_read_and_remain_in_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'user']);
        $application = \App\Models\Application::factory()->create([
            'user_id' => $applicant->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.applications.approve', $application))
            ->assertRedirect();

        $approvedNotification = $applicant->notifications()->latest()->firstOrFail();
        $this->assertSame('approved', $approvedNotification->data['status']);

        $this->post(route('logout'))->assertRedirect('/');
        $this->post(route('login'), [
            'email' => $applicant->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-application-status-toast', false)
            ->assertSee('data-status="approved"', false)
            ->assertSee('Mark as read')
            ->assertSee('status-toast-countdown')
            ->assertSee('10000');

        $this->get(route('applicant.notifications.recent'))
            ->assertOk()
            ->assertSee('Application approved')
            ->assertSee('Mark as read')
            ->assertDontSee('data-notification-id="' . $approvedNotification->id . '"', false);

        $this->postJson(route('notifications.read', $approvedNotification->id), [
            'redirect_to' => 'back',
        ])->assertJson(['ok' => true]);
        $approvedNotification->refresh();
        $this->assertNotNull($approvedNotification->read_at);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-notification-id="' . $approvedNotification->id . '"', false);

        $this->actingAs($admin)
            ->post(route('admin.applications.deny', $application))
            ->assertRedirect();
        $this->assertSame('denied', $application->fresh()->status);
        $this->assertSame(2, $applicant->notifications()->where('type', \App\Notifications\ApplicantPortalUpdate::class)->count());

        $deniedNotification = $applicant->notifications()
            ->whereJsonContains('data->status', 'denied')
            ->firstOrFail();

        $this->actingAs($applicant)
            ->get(route('applicant.notifications.recent'))
            ->assertOk()
            ->assertSee('Application approved')
            ->assertSee('Read ')
            ->assertSee('Application denied')
            ->assertDontSee('data-notification-id="' . $deniedNotification->id . '"', false);

        $this->assertSame('denied', $deniedNotification->data['status']);

        $this->post(route('logout'))->assertRedirect('/');
        $this->post(route('login'), [
            'email' => $applicant->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-application-status-toast', false)
            ->assertSee('data-notification-id="' . $deniedNotification->id . '"', false);

        $this->actingAs($admin)
            ->post(route('admin.applications.deny', $application))
            ->assertRedirect();
        $this->assertSame(2, $applicant->notifications()->where('type', \App\Notifications\ApplicantPortalUpdate::class)->count());

        $this->actingAs($applicant)
            ->get(route('applicant.notifications.previous'))
            ->assertOk()
            ->assertSee('Application approved')
            ->assertSee('Application denied')
            ->assertSee('Unread — delivered in-app');
    }

    public function test_admin_feedback_appears_in_recent_notifications_and_remains_in_previous_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'user']);
        $application = \App\Models\Application::factory()->create([
            'user_id' => $applicant->id,
            'status' => 'pending',
        ]);
        $feedback = 'Please upload a clearer enrollment certificate.';

        $this->actingAs($admin)
            ->post(route('admin.applications.comment', $application), [
                'admin_comment' => $feedback,
            ])
            ->assertSessionHas('success');

        $notification = $applicant->notifications()->latest()->firstOrFail();
        $this->assertSame('application_feedback', $notification->data['event']);

        $this->actingAs($applicant)
            ->get(route('applicant.notifications.recent'))
            ->assertOk()
            ->assertSee('Document requirement update')
            ->assertSee($feedback)
            ->assertSee('Unread');

        $this->get(route('applicant.notifications.previous'))
            ->assertOk()
            ->assertSee('Document requirement update')
            ->assertSee($feedback);
    }

    public function test_previous_notifications_shows_older_recorded_sign_ins_with_device_details(): void
    {
        $user = User::factory()->create();
        \App\Models\ApplicantLoginActivity::create([
            'user_id' => $user->id,
            'ip_address' => '192.0.2.15',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile Safari/604.1',
            'logged_in_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get(route('applicant.notifications.previous'))
            ->assertOk()
            ->assertSee('Account sign-in')
            ->assertSee('Safari on iPhone (Mobile)')
            ->assertSee('IP: 192.0.2.15');
    }
}