<?php

namespace App\Http\Requests\ApplicantSettings;

use Illuminate\Foundation\Http\FormRequest;

class RequestEmailChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'user';
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'current_password' => ['required', 'current_password'],
        ];
    }
}
