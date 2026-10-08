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
        $emailRules = [
            'required',
            'string',
            'lowercase',
            'email',
            'max:255',
        ];

        if ($this->user()->role === 'user') {
            $emailRules[] = Rule::in([$this->user()->email]);
        } else {
            $emailRules[] = Rule::unique(User::class)->ignore($this->user()->id);
        }

        $isApplicant = $this->user()->role === 'user';
        $contactNumberRules = [$isApplicant ? 'required' : 'nullable', 'string', 'max:50'];
        if ($isApplicant) {
            $contactNumberRules[] = 'regex:/^(09\d{9}|\+639\d{9})$/D';
            $existingContactNumber = $this->user()->profile()->value('contact_number') ?: $this->user()->contact_number;
            if (filled($existingContactNumber)) {
                $contactNumberRules[] = Rule::in([$existingContactNumber]);
            }
        }

        $requiredOrOptional = static fn (array $rules): array => $isApplicant
            ? ['required', ...$rules]
            : ['nullable', ...$rules];

        return [
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'name' => ['sometimes', 'string', 'max:255'],
            'last_name' => $requiredOrOptional(['string', 'max:255']),
            'first_name' => $requiredOrOptional(['string', 'max:255']),
            'middle_name' => $requiredOrOptional(['string', 'max:255']),
            'sex' => $requiredOrOptional([Rule::in(['Male', 'Female'])]),
            'date_of_birth' => $requiredOrOptional(['date']),
            'place_of_birth' => $requiredOrOptional(['string', 'max:255']),
            'status' => $requiredOrOptional([Rule::in(['Single', 'Married'])]),
            'citizenship' => $requiredOrOptional([Rule::in(['Filipino', 'Foreign'])]),
            'email' => $emailRules,
            'social_media' => $requiredOrOptional(['string', 'max:255']),
            'contact_number' => $contactNumberRules,
            'present_address' => $requiredOrOptional(['string', 'max:500']),
            'permanent_address' => $requiredOrOptional(['string', 'max:500']),
            'applicant_category' => $requiredOrOptional([Rule::in([
                'student',
                'out_of_school_youth',
                'working_student',
            ])]),
            'education_history' => ['nullable', 'array'],
            'education_history.*.level' => ['required_with:education_history', 'string', 'max:255'],
            'education_history.*.school' => ['nullable', 'string', 'max:255'],
            'education_history.*.course' => ['nullable', 'string', 'max:255'],
            'education_history.*.year_level' => ['nullable', 'string', 'max:100'],
            'education_history.*.date_attended' => ['nullable', 'string', 'max:255'],
            'father_name' => $requiredOrOptional(['string', 'max:255']),
            'father_contact_number' => $requiredOrOptional(['string', 'max:50']),
            'father_occupation' => $requiredOrOptional(['string', 'max:255']),
            'mother_name' => $requiredOrOptional(['string', 'max:255']),
            'mother_contact_number' => $requiredOrOptional(['string', 'max:50']),
            'mother_occupation' => $requiredOrOptional(['string', 'max:255']),
            'parent_status_details' => $isApplicant
                ? ['required', 'array', 'min:1']
                : ['nullable', 'array'],
            'parent_status_details.*' => ['string', Rule::in([
                'Living Together',
                'Solo Parent',
                'Orphan',
                'Guardian',
            ])],
            'special_skills' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.in' => 'Change your email from your profile so you can verify the new address.',
            'contact_number.regex' => 'Enter a Philippine mobile number like 09XXXXXXXXX or +639XXXXXXXXX.',
            'contact_number.in' => 'Change your mobile number in Settings.',
        ];
    }
}
