<?php

namespace Tests\Feature;

use App\Models\ApplicantSetting;
use App\Models\Appointment;
use App\Models\Application;
use App\Models\User;
use App\Notifications\ApplicantPortalUpdate;
use App\Services\ApplicantNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ApplicantNotificationEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_preferences_enable_mail_to_the_registered_email_address(): void
    {
        $applicant = User::factory()->create(['role' => 'user']);
        ApplicantSetting::create([
            'user_id' => $applicant->id,
            'email_notifications' => true,
            'system_notifications' => true,
            'application_updates' => true,
        ]);
        $notification = new ApplicantPortalUpdate(
            'notify_documents',
            'Application approved',
            'Your application was approved.',
            ['status' => 'approved'],
        );

        $this->assertSame(['database', 'mail'], $notification->via($applicant));
        $this->assertSame($applicant->email, $applicant->routeNotificationFor('mail', $notification));
    }

    public function test_announcement_email_shows_the_admin_title_and_full_content(): void
    {
        $applicant = User::factory()->create(['role' => 'user']);
        $announcementTitle = 'SPES application schedule';
        $announcementContent = "Applications open on October 15.\nSubmit your documents at the PESO office.";
        $notification = new ApplicantPortalUpdate(
            'notify_announcements',
            'New SPES announcement: '.$announcementTitle,
            $announcementContent,
            [
                'news_id' => 42,
                'announcement_title' => $announcementTitle,
                'announcement_content' => $announcementContent,
            ],
        );

        $mail = $notification->toMail($applicant);

        $this->assertSame(
            'Special Program for Employment of Students (SPES): New SPES announcement: '.$announcementTitle,
            $mail->subject,
        );
        $this->assertContains('Announcement: '.$announcementTitle, $mail->introLines);
        $this->assertContains('Applications open on October 15.', $mail->introLines);
        $this->assertContains('Submit your documents at the PESO office.', $mail->introLines);
    }

    public function test_email_notifications_can_be_disabled_without_disabling_system_notifications(): void
    {
        $applicant = User::factory()->create(['role' => 'user']);
        ApplicantSetting::create([
            'user_id' => $applicant->id,
            'email_notifications' => false,
            'system_notifications' => true,
            'application_updates' => true,
        ]);
        $notification = new ApplicantPortalUpdate(
            'notify_documents',
            'Application approved',
            'Your application was approved.',
            ['status' => 'approved'],
        );

        $this->assertSame(['database'], $notification->via($applicant));
    }

    public function test_notification_preferences_save_unchecked_options_as_disabled(): void
    {
        $applicant = User::factory()->create(['role' => 'user']);

        $this->actingAs($applicant)
            ->put(route('settings.notifications.update'), [])
            ->assertRedirect()
            ->assertSessionHas('settings_success');

        $this->assertDatabaseHas('applicant_settings', [
            'user_id' => $applicant->id,
            'email_notifications' => false,
            'system_notifications' => false,
            'application_updates' => false,
        ]);
    }

    public function test_notification_delivery_is_recorded_and_duplicate_events_are_not_resent(): void
    {
        Mail::fake();
        $applicant = User::factory()->create(['role' => 'user']);
        ApplicantSetting::create([
            'user_id' => $applicant->id,
            'email_notifications' => true,
            'system_notifications' => true,
            'application_updates' => true,
        ]);
        $notification = new ApplicantPortalUpdate(
            'notify_documents',
            'Application approved',
            'Your SPES application has been approved.',
            ['application_id' => 45, 'status' => 'approved', 'event' => 'application_approved'],
        );
        $service = app(ApplicantNotificationService::class);

        $service->notifyApplicant($applicant, $notification, 'application:45:approved:123');
        $service->notifyApplicant($applicant, $notification, 'application:45:approved:123');

        $this->assertDatabaseHas('applicant_notification_deliveries', [
            'user_id' => $applicant->id,
            'event_hash' => hash('sha256', 'application:45:approved:123'),
            'channel' => 'mail',
            'status' => 'sent',
            'attempts' => 1,
        ]);
        $this->assertSame(1, $applicant->notifications()->count());
        $this->assertDatabaseHas('applicant_notification_deliveries', [
            'user_id' => $applicant->id,
            'event_hash' => hash('sha256', 'application:45:approved:123'),
            'channel' => 'database',
            'status' => 'sent',
            'attempts' => 1,
        ]);
    }

    public function test_eligible_appointment_reminder_is_sent_once_by_the_scheduled_command(): void
    {
        Mail::fake();
        $now = now()->startOfHour();
        $this->travelTo($now);
        $applicant = User::factory()->create(['role' => 'user']);
        Application::factory()->for($applicant)->create(['status' => 'approved']);
        ApplicantSetting::create([
            'user_id' => $applicant->id,
            'email_notifications' => true,
            'system_notifications' => true,
            'notify_appointments' => true,
            'reminder_notifications' => true,
        ]);
        $appointment = Appointment::create([
            'title' => 'SPES Orientation',
            'description' => 'Bring your identification.',
            'location' => 'PESO Office',
            'starts_at' => $now->copy()->addHours(23)->addMinutes(30),
            'is_published' => true,
            'target_audience' => 'approved_applicants',
        ]);

        Artisan::call('appointments:send-reminders');
        Artisan::call('appointments:send-reminders');

        $this->assertSame(1, $applicant->notifications()->count());
        $this->assertDatabaseHas('applicant_notification_deliveries', [
            'user_id' => $applicant->id,
            'event_hash' => hash('sha256', "appointment:{$appointment->id}:reminder:{$appointment->starts_at->getTimestamp()}"),
            'channel' => 'mail',
            'status' => 'sent',
            'attempts' => 1,
        ]);
    }

    public function test_appointment_day_notice_emails_admin_entered_content_once_to_matching_applicants(): void
    {
        Mail::fake();
        $now = now()->setDate(2026, 10, 10)->setTime(9, 0);
        $this->travelTo($now);
        $applicant = User::factory()->create(['role' => 'user']);
        $otherApplicant = User::factory()->create(['role' => 'user']);
        Application::factory()->for($applicant)->create(['status' => 'approved']);
        Application::factory()->for($otherApplicant)->create(['status' => 'pending']);
        ApplicantSetting::create([
            'user_id' => $applicant->id,
            'email_notifications' => true,
            'system_notifications' => true,
            'notify_appointments' => true,
            'reminder_notifications' => true,
        ]);
        ApplicantSetting::create([
            'user_id' => $otherApplicant->id,
            'email_notifications' => true,
            'system_notifications' => true,
            'notify_appointments' => true,
            'reminder_notifications' => true,
        ]);
        $appointment = Appointment::create([
            'title' => 'Application document review',
            'description' => 'Bring your signed Form 2 and original school ID.',
            'location' => 'PESO Lal-lo office',
            'starts_at' => $now->copy()->setTime(14, 30),
            'is_published' => true,
            'target_audience' => 'approved_applicants',
        ]);
        Appointment::create([
            'title' => 'Tomorrow review',
            'description' => 'This must not be included in today’s notice.',
            'starts_at' => $now->copy()->addDay(),
            'is_published' => true,
            'target_audience' => 'approved_applicants',
        ]);
        Appointment::create([
            'title' => 'Unpublished review',
            'description' => 'This draft must not be sent.',
            'starts_at' => $now->copy()->setTime(16, 0),
            'is_published' => false,
            'target_audience' => 'approved_applicants',
        ]);

        Artisan::call('appointments:send-day-of-notices');
        Artisan::call('appointments:send-day-of-notices');

        $this->assertSame(1, $applicant->notifications()->count());
        $this->assertSame(0, $otherApplicant->notifications()->count());
        $storedNotification = $applicant->notifications()->firstOrFail();
        $this->assertSame($appointment->title, $storedNotification->data['appointment_title']);
        $this->assertSame($appointment->description, $storedNotification->data['appointment_description']);
        $this->assertSame($appointment->location, $storedNotification->data['appointment_location']);
        $notification = new ApplicantPortalUpdate(
            'notify_appointments',
            'Appointment today: '.$appointment->title,
            'Appointment details',
            [
                'appointment_id' => $appointment->id,
                'appointment_title' => $appointment->title,
                'appointment_description' => $appointment->description,
                'appointment_date' => $appointment->starts_at->format('M j, Y g:i A'),
                'appointment_location' => $appointment->location,
                'event' => 'appointment_day_of',
            ],
        );
        $mail = $notification->toMail($applicant);
        $this->assertContains('Appointment: '.$appointment->title, $mail->introLines);
        $this->assertContains('Date and time: '.$appointment->starts_at->format('M j, Y g:i A'), $mail->introLines);
        $this->assertContains('Location: '.$appointment->location, $mail->introLines);
        $this->assertContains('Details: '.$appointment->description, $mail->introLines);
        $this->assertDatabaseHas('applicant_notification_deliveries', [
            'user_id' => $applicant->id,
            'event_hash' => hash('sha256', "appointment:{$appointment->id}:day-of:{$appointment->starts_at->getTimestamp()}"),
            'channel' => 'mail',
            'status' => 'sent',
            'attempts' => 1,
        ]);
    }

    public function test_failed_mail_delivery_is_logged_and_a_retry_updates_delivery_status(): void
    {
        Log::spy();
        $applicant = User::factory()->create(['role' => 'user']);
        ApplicantSetting::create([
            'user_id' => $applicant->id,
            'email_notifications' => true,
            'system_notifications' => false,
            'application_updates' => true,
        ]);
        $notification = new ApplicantPortalUpdate(
            'notify_documents',
            'Application approved',
            'Your SPES application has been approved.',
            ['application_id' => 49, 'status' => 'approved'],
        );
        $mailAttempts = 0;
        $channelManager = Mockery::mock(ChannelManager::class);
        $channelManager->shouldReceive('sendNow')
            ->twice()
            ->andReturnUsing(function () use (&$mailAttempts): void {
                if ($mailAttempts++ === 0) {
                    throw new RuntimeException('SMTP credentials must not appear in logs.');
                }
            });
        app()->instance(ChannelManager::class, $channelManager);
        $service = app(ApplicantNotificationService::class);

        $service->notifyApplicant($applicant, $notification, 'application:49:approved:123');
        $this->assertDatabaseHas('applicant_notification_deliveries', [
            'user_id' => $applicant->id,
            'event_hash' => hash('sha256', 'application:49:approved:123'),
            'channel' => 'mail',
            'status' => 'failed',
            'attempts' => 1,
            'last_error' => 'RuntimeException',
        ]);

        $service->notifyApplicant($applicant, $notification, 'application:49:approved:123');

        $this->assertDatabaseHas('applicant_notification_deliveries', [
            'user_id' => $applicant->id,
            'event_hash' => hash('sha256', 'application:49:approved:123'),
            'channel' => 'mail',
            'status' => 'sent',
            'attempts' => 2,
            'last_error' => null,
        ]);
        Log::shouldHaveReceived('error')->once();
    }
}
