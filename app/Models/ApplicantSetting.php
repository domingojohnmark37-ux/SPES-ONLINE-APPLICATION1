<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantSetting extends Model
{
    protected $table = 'applicant_settings';

    protected $fillable = [
        'user_id',
        'notify_application_status',
        'notify_appointments',
        'notify_announcements',
        'notify_documents',
        'email_notifications',
        'system_notifications',
        'application_updates',
        'approval_rejection_notifications',
        'reminder_notifications',
        'notification_method',
        'appearance',
        'theme_preference',
        'language',
        'timezone',
        'date_format',
        'time_format',
        'number_format',
        'sidebar_behavior',
        'font_size',
        'profile_visibility',
        'personal_information_visibility',
        'data_sharing_preferences',
        'activity_visibility',
        'high_contrast_mode',
        'larger_text',
        'reduced_motion',
        'keyboard_navigation',
        'screen_reader_support',
        'two_factor_authentication',
        'login_notifications',
        'security_questions',
        'password_requirements',
    ];

    protected function casts(): array
    {
        return [
            'notify_application_status' => 'boolean',
            'notify_appointments' => 'boolean',
            'notify_announcements' => 'boolean',
            'notify_documents' => 'boolean',
            'email_notifications' => 'boolean',
            'system_notifications' => 'boolean',
            'application_updates' => 'boolean',
            'approval_rejection_notifications' => 'boolean',
            'reminder_notifications' => 'boolean',
            'high_contrast_mode' => 'boolean',
            'larger_text' => 'boolean',
            'reduced_motion' => 'boolean',
            'keyboard_navigation' => 'boolean',
            'screen_reader_support' => 'boolean',
            'two_factor_authentication' => 'boolean',
            'login_notifications' => 'boolean',
            'security_questions' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
