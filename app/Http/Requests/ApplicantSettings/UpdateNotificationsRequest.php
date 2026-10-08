<?php

namespace App\Http\Requests\ApplicantSettings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'user';
    }

    public function rules(): array
    {
        return [
            'email_notifications' => ['nullable', 'boolean'],
            'system_notifications' => ['nullable', 'boolean'],
            'application_updates' => ['nullable', 'boolean'],
            'approval_rejection_notifications' => ['nullable', 'boolean'],
            'reminder_notifications' => ['nullable', 'boolean'],
            'notification_method' => ['nullable', 'in:in_app,email'],
        ];
    }
}
