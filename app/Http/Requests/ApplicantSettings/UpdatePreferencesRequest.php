<?php

namespace App\Http\Requests\ApplicantSettings;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'user';
    }

    public function rules(): array
    {
        return [
            'appearance' => ['nullable', 'in:light,dark,system'],
            'theme_preference' => ['nullable', 'in:light,dark,system'],
            'language' => ['nullable', 'in:en,fil'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'date_format' => ['nullable', 'string', 'max:20'],
            'time_format' => ['nullable', 'in:12h,24h'],
            'number_format' => ['nullable', 'in:default,comma,dot'],
            'sidebar_behavior' => ['nullable', 'in:auto,expanded,collapsed'],
            'font_size' => ['nullable', 'in:small,medium,large'],
            'profile_visibility' => ['nullable', 'in:private,members,public'],
            'personal_information_visibility' => ['nullable', 'in:private,members,public'],
            'data_sharing_preferences' => ['nullable', 'in:limited,shared,private'],
            'activity_visibility' => ['nullable', 'in:private,public'],
            'high_contrast_mode' => ['nullable', 'boolean'],
            'larger_text' => ['nullable', 'boolean'],
            'reduced_motion' => ['nullable', 'boolean'],
            'keyboard_navigation' => ['nullable', 'boolean'],
            'screen_reader_support' => ['nullable', 'boolean'],
            'two_factor_authentication' => ['nullable', 'boolean'],
            'login_notifications' => ['nullable', 'boolean'],
            'security_questions' => ['nullable', 'boolean'],
            'password_requirements' => ['nullable', 'in:standard,strong,strict'],
        ];
    }
}
