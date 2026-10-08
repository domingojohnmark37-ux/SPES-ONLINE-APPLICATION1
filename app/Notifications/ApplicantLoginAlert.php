<?php

namespace App\Notifications;

use App\Models\ApplicantSetting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicantLoginAlert extends Notification
{
    public function __construct(
        private readonly ?int $activityId,
        private readonly string $device,
        private readonly ?string $ipAddress,
    ) {
    }

    public function via(object $notifiable): array
    {
        if ($notifiable->role !== 'user') {
            return ['mail'];
        }

        $channels = ['database'];
        $settings = ApplicantSetting::firstOrNew(['user_id' => $notifiable->getKey()]);

        if ($settings->login_notifications ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('New sign-in to your SPES account')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Someone signed in to your SPES account.')
            ->line('Device: '.$this->device);

        if ($this->ipAddress !== null) {
            $mail->line('IP address: '.$this->ipAddress);
        }

        return $mail
            ->line('If this was you, no action is needed. If you do not recognize this sign-in, reset your password immediately.')
            ->action('Reset Password', route('password.request'))
            ->salutation('PESO Lal-lo SPES Portal');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New sign-in to your SPES account',
            'message' => "Someone signed in from {$this->device}.",
            'category' => 'system',
            'device' => $this->device,
            'ip_address' => $this->ipAddress,
            'login_activity_id' => $this->activityId,
        ];
    }
}
