<?php

namespace App\Http\Requests\ApplicantSettings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestEmailChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'user';
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()?->id)],
            'current_password' => ['required', 'current_password'],
        ];
    }
}
