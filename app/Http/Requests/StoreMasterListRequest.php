<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMasterListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:100',
            'spes_status' => 'nullable|in:new,baby',
            'search' => 'nullable|string|max:255',
            'sort' => 'nullable|in:name_asc,name_desc',
            'placement' => 'nullable|array',
            'placement.*' => 'array:nature_of_work,place_of_assignment,wage_rate,company_share',
            'placement.*.nature_of_work' => 'nullable|string|max:255',
            'placement.*.place_of_assignment' => 'nullable|string|max:255',
            'placement.*.wage_rate' => 'nullable|integer|min:1',
            'placement.*.company_share' => 'nullable|integer|min:1',
        ];
    }
}
