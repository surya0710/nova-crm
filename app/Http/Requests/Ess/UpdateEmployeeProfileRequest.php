<?php

namespace App\Http\Requests\Ess;

use App\Services\Hrms\EssContext;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = app(EssContext::class)->requireEmployee($this->user());

        return $this->user()?->can('updateOwn', $employee) ?? false;
    }

    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:100'],
            'emergency_contacts' => ['nullable', 'array'],
            'emergency_contacts.*.name' => ['required_with:emergency_contacts', 'string', 'max:255'],
            'emergency_contacts.*.relationship' => ['nullable', 'string', 'max:100'],
            'emergency_contacts.*.phone' => ['required_with:emergency_contacts', 'string', 'max:50'],
            'emergency_contacts.*.alternate_mobile' => ['nullable', 'string', 'max:50'],
            'emergency_contacts.*.email' => ['nullable', 'email', 'max:255'],
            'emergency_contacts.*.address' => ['nullable', 'string', 'max:2000'],
            'emergency_contacts.*.is_primary' => ['sometimes', 'boolean'],

            'skills' => ['nullable', 'array'],
            'skills.*.skill' => ['required', 'string', 'max:255'],
            'skills.*.proficiency' => ['required', 'in:beginner,intermediate,advanced,expert'],
            'skills.*.years_of_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'skills.*.last_used' => ['nullable', 'date'],
            'skills.*.notes' => ['nullable', 'string', 'max:2000'],

            'certifications' => ['nullable', 'array'],
            'certifications.*.name' => ['required', 'string', 'max:255'],
            'certifications.*.issuing_organization' => ['nullable', 'string', 'max:255'],
            'certifications.*.credential_number' => ['nullable', 'string', 'max:255'],
            'certifications.*.issue_date' => ['nullable', 'date'],
            'certifications.*.expiry_date' => ['nullable', 'date'],
            'certifications.*.credential_url' => ['nullable', 'url', 'max:500'],
            'certifications.*.status' => ['nullable', 'in:active,expired,revoked'],

            'educations' => ['nullable', 'array'],
            'educations.*.institution' => ['required', 'string', 'max:255'],
            'educations.*.degree' => ['required', 'string', 'max:255'],
            'educations.*.field_of_study' => ['nullable', 'string', 'max:255'],
            'educations.*.start_date' => ['nullable', 'date'],
            'educations.*.end_date' => ['nullable', 'date'],
            'educations.*.grade' => ['nullable', 'string', 'max:100'],
            'educations.*.description' => ['nullable', 'string', 'max:5000'],

            'experiences' => ['nullable', 'array'],
            'experiences.*.company' => ['required', 'string', 'max:255'],
            'experiences.*.title' => ['nullable', 'string', 'max:255'],
            'experiences.*.employment_type' => ['nullable', 'string', 'max:50'],
            'experiences.*.start_date' => ['nullable', 'date'],
            'experiences.*.end_date' => ['nullable', 'date'],
            'experiences.*.technologies' => ['nullable', 'string', 'max:2000'],
            'experiences.*.description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
