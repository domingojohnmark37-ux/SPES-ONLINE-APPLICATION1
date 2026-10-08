<?php

namespace App\Http\Requests\ApplicantSettings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMobileNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'user';
    }

    public function rules(): array
    {
        return [
            'mobile_number' => ['required', 'regex:/^(09\d{9}|\+639\d{9})$/D'],
        ];
    }
}
