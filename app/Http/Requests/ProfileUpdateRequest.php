<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', Rule::in(['Male', 'Female'])],
            'date_of_birth' => ['nullable', 'date'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'citizenship' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'social_media' => ['nullable', 'string', 'max:255'],
            'gsis_beneficiary' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'present_address' => ['nullable', 'string', 'max:500'],
            'permanent_address' => ['nullable', 'string', 'max:500'],
            'applicant_category' => ['nullable', Rule::in([
                'student',
                'out_of_school_youth',
                'working_student',
            ])],
            'education_history' => ['nullable', 'array'],
            'education_history.*.level' => ['required_with:education_history', 'string', 'max:255'],
            'education_history.*.school' => ['nullable', 'string', 'max:255'],
            'education_history.*.course' => ['nullable', 'string', 'max:255'],
            'education_history.*.year_level' => ['nullable', 'string', 'max:100'],
            'education_history.*.date_attended' => ['nullable', 'string', 'max:255'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'father_contact_number' => ['nullable', 'string', 'max:50'],
            'father_occupation' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'mother_contact_number' => ['nullable', 'string', 'max:50'],
            'mother_occupation' => ['nullable', 'string', 'max:255'],
            'parent_status_details' => ['nullable', 'array'],
            'parent_status_details.*' => ['string', Rule::in([
                'Living Together',
                'Solo Parent',
                'Orphan',
                'Guardian',
            ])],
            'special_skills' => ['nullable', 'string', 'max:500'],
        ];
    }
}
