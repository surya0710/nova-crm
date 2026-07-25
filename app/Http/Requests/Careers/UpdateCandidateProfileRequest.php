<?php

namespace App\Http\Requests\Careers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCandidateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'current_company' => ['nullable', 'string', 'max:150'],
            'current_designation' => ['nullable', 'string', 'max:150'],
            'experience' => ['nullable', 'string', 'max:100'],
            'notice_period' => ['nullable', 'string', 'max:50'],
            'expected_salary' => ['nullable', 'numeric', 'min:0'],
            'skills' => ['nullable', 'string'],
            'linkedin' => ['nullable', 'url', 'max:255'],
            'github' => ['nullable', 'url', 'max:255'],
            'portfolio' => ['nullable', 'url', 'max:255'],
            'availability_date' => ['nullable', 'date'],
            'preferred_locations' => ['nullable', 'array'],
            'preferred_locations.*' => ['string', 'max:150'],
            'education' => ['nullable', 'array'],
            'work_experience' => ['nullable', 'array'],
            'languages' => ['nullable', 'array'],
            'certifications' => ['nullable', 'array'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
