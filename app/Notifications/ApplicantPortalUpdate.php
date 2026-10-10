<?php

namespace App\Notifications;

use App\Models\ApplicantSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicantPortalUpdate extends Notification
{
    use Queueable;

    public function __construct(
        public string $preference,
        public string $title,
        public string $message,
        public array $data = [],
    ) {}

    public function via(object $notifiable): array
    {
        $settings = ApplicantSetting::firstOrNew(['user_id' => $notifiable->getKey()]);
        $isApplicationUpdate = array_key_exists('status', $this->data)
            || in_array($this->data['event'] ?? null, [
                'application_feedback',
                'application_submitted',
                'application_resubmitted',
            ], true);
        $isApplicationDecision = in_array($this->data['status'] ?? null, ['approved', 'denied'], true)
            || ($this->data['event'] ?? null) === 'application_feedback';
        $isAppointment = isset($this->data['appointment_id']);
        $isAnnouncement = isset($this->data['news_id']);

        if ($isApplicationUpdate) {
            $enabled = ($settings->application_updates ?? true)
                && (! $isApplicationDecision || ($settings->approval_rejection_notifications ?? true));
        } elseif ($isAppointment) {
            $enabled = ($settings->notify_appointments ?? true)
                && ($settings->reminder_notifications ?? true);
        } elseif ($isAnnouncement) {
            $enabled = $settings->{$this->preference} ?? true;
        } else {
            $enabled = $settings->{$this->preference} ?? true;
        }

        if (! $enabled) {
            return [];
        }

        $channels = [];
        if ($settings->system_notifications ?? true) {
            $channels[] = 'database';
        }

        if (($settings->email_notifications ?? true) && $enabled) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Special Program for Employment of Students (SPES): '.$this->title)
            ->greeting('Hello '.$notifiable->name.',');

        if (($this->data['event'] ?? null) === 'appointment_day_of') {
            $mail->line('Your SPES appointment is scheduled for today.')
                ->line('Appointment: '.$this->data['appointment_title'])
                ->line('Date and time: '.$this->data['appointment_date']);
            if (filled($this->data['appointment_location'] ?? null)) {
                $mail->line('Location: '.$this->data['appointment_location']);
            }
            if (filled($this->data['appointment_description'] ?? null)) {
                $mail->line('Details: '.$this->data['appointment_description']);
            }
        } elseif (isset($this->data['news_id'])) {
            $announcementContent = $this->data['announcement_content'] ?? $this->message;
            $announcementContent = preg_replace('/<br\s*\/?>|<\/(?:p|div|li)>/i', "\n", $announcementContent);
            $announcementContent = html_entity_decode(strip_tags($announcementContent), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $mail->line('A new announcement has been published by PESO Lal-lo.')
                ->line('Announcement: '.($this->data['announcement_title'] ?? $this->title));
            foreach (preg_split('/\R+/', trim($announcementContent)) as $contentLine) {
                if (filled($contentLine)) {
                    $mail->line($contentLine);
                }
            }
        } else {
            $mail->line($this->message);
        }

        return $mail
            ->action('Open the SPES Portal', route('login'))
            ->salutation('PESO Lal-lo SPES Portal');
    }

    public function toDatabase(object $notifiable): array
    {
        return array_merge([
            'title' => $this->title,
            'message' => $this->message,
        ], $this->data);
    }
}
