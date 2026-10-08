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

        if (
            in_array($this->data['status'] ?? null, ['approved', 'denied'], true)
            || ($this->data['event'] ?? null) === 'application_feedback'
        ) {
            return ($settings->notification_method ?? 'in_app') === 'email'
                ? ['database', 'mail']
                : ['database'];
        }

        if (isset($this->data['appointment_id'])) {
            $channels = ['database'];
            if (($settings->notify_appointments ?? true) && ($settings->notification_method ?? 'in_app') === 'email') {
                $channels[] = 'mail';
            }

            return $channels;
        }

        if (! ($settings->{$this->preference} ?? true)) {
            return [];
        }

        return ($settings->notification_method ?? 'in_app') === 'email'
            ? ['mail']
            : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SPES Portal: '.$this->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->message)
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
