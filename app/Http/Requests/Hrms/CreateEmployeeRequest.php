<?php

namespace App\Http\Requests\Hrms;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Employee::class) ?? false;
    }

    public function rules(): array
    {
        return $this->baseRules();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'emergency_contacts' => $this->filterProfileRows($this->input('emergency_contacts'), ['name', 'phone']),
            'educations' => $this->filterProfileRows($this->input('educations'), ['degree', 'institution']),
            'experiences' => $this->filterProfileRows($this->input('experiences'), ['company']),
            'skills' => $this->filterProfileRows($this->input('skills'), ['skill']),
            'certifications' => $this->filterProfileRows($this->input('certifications'), ['name']),
        ]);
    }

    /**
     * @param  mixed  $rows
     * @param  list<string>  $requiredKeys
     * @return list<array<string, mixed>>|null
     */
    protected function filterProfileRows(mixed $rows, array $requiredKeys): ?array
    {
        if (! is_array($rows)) {
            return null;
        }

        $filtered = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $hasValue = false;
            foreach ($requiredKeys as $key) {
                if (filled($row[$key] ?? null)) {
                    $hasValue = true;
                    break;
                }
            }

            if ($hasValue) {
                $filtered[] = $row;
            }
        }

        return $filtered;
    }

    protected function baseRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'branch_id' => ['nullable', Rule::exists('hrms_branches', 'id')],
            'department_id' => ['nullable', Rule::exists('hrms_departments', 'id')],
            'designation_id' => ['nullable', Rule::exists('hrms_designations', 'id')],
            'reporting_manager_id' => ['nullable', Rule::exists('employees', 'id')],
            'employment_type' => ['required', Rule::in(array_keys(config('hrms.employment_types')))],
            'status' => ['required', Rule::in(array_keys(config('hrms.employment_statuses')))],
            'joining_date' => ['nullable', 'date'],
            'probation_end_date' => ['nullable', 'date'],
            'exit_date' => ['nullable', 'date'],

            'emergency_contacts' => ['nullable', 'array'],
            'emergency_contacts.*.name' => ['required', 'string', 'max:255'],
            'emergency_contacts.*.relationship' => ['nullable', 'string', 'max:100'],
            'emergency_contacts.*.phone' => ['required', 'string', 'max:50'],
            'emergency_contacts.*.alternate_mobile' => ['nullable', 'string', 'max:50'],
            'emergency_contacts.*.email' => ['nullable', 'email', 'max:255'],
            'emergency_contacts.*.address' => ['nullable', 'string', 'max:2000'],
            'emergency_contacts.*.is_primary' => ['sometimes', 'boolean'],

            'bank_accounts' => ['nullable', 'array'],
            'bank_accounts.*.bank_name' => ['required', 'string', 'max:255'],
            'bank_accounts.*.account_holder_name' => ['required', 'string', 'max:255'],
            'bank_accounts.*.account_number' => ['required', 'string', 'max:50'],
            'bank_accounts.*.ifsc_or_swift' => ['nullable', 'string', 'max:50'],

            'identities' => ['nullable', 'array'],
            'identities.*.type' => ['required', Rule::in(array_keys(config('hrms.identity_document_types')))],
            'identities.*.number' => ['required', 'string', 'max:100'],
            'identities.*.issued_on' => ['nullable', 'date'],
            'identities.*.expires_on' => ['nullable', 'date'],

            'educations' => ['nullable', 'array'],
            'educations.*.degree' => ['required', 'string', 'max:255'],
            'educations.*.institution' => ['required', 'string', 'max:255'],
            'educations.*.field_of_study' => ['nullable', 'string', 'max:255'],
            'educations.*.specialization' => ['nullable', 'string', 'max:255'],
            'educations.*.start_date' => ['nullable', 'date'],
            'educations.*.end_date' => ['nullable', 'date', 'after_or_equal:educations.*.start_date'],
            'educations.*.start_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'educations.*.end_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'educations.*.grade' => ['nullable', 'string', 'max:100'],
            'educations.*.description' => ['nullable', 'string', 'max:5000'],

            'experiences' => ['nullable', 'array'],
            'experiences.*.company' => ['required', 'string', 'max:255'],
            'experiences.*.title' => ['nullable', 'string', 'max:255'],
            'experiences.*.designation' => ['nullable', 'string', 'max:255'],
            'experiences.*.employment_type' => ['nullable', Rule::in(array_keys(config('hrms.employment_types')))],
            'experiences.*.start_date' => ['nullable', 'date'],
            'experiences.*.end_date' => ['nullable', 'date', 'after_or_equal:experiences.*.start_date'],
            'experiences.*.technologies' => ['nullable', 'string', 'max:2000'],
            'experiences.*.description' => ['nullable', 'string', 'max:5000'],
            'experiences.*.responsibilities' => ['nullable', 'string', 'max:5000'],

            'skills' => ['nullable', 'array'],
            'skills.*.skill' => ['required', 'string', 'max:255'],
            'skills.*.proficiency' => ['required', Rule::in(array_keys(config('hrms.skill_proficiencies')))],
            'skills.*.years_of_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'skills.*.last_used' => ['nullable', 'date'],
            'skills.*.notes' => ['nullable', 'string', 'max:2000'],

            'certifications' => ['nullable', 'array'],
            'certifications.*.name' => ['required', 'string', 'max:255'],
            'certifications.*.issuing_organization' => ['nullable', 'string', 'max:255'],
            'certifications.*.credential_number' => ['nullable', 'string', 'max:255'],
            'certifications.*.issue_date' => ['nullable', 'date'],
            'certifications.*.expiry_date' => ['nullable', 'date', 'after_or_equal:certifications.*.issue_date'],
            'certifications.*.credential_url' => ['nullable', 'url', 'max:500'],
            'certifications.*.status' => ['nullable', Rule::in(array_keys(config('hrms.certification_statuses')))],

            'create_user' => ['sometimes', 'boolean'],
            'user_email' => ['nullable', 'required_if:create_user,1', 'email', 'max:255'],
            'role' => ['nullable', 'string', Rule::in(array_keys(config('rbac.roles', [])))],
            'send_invitation' => ['sometimes', 'boolean'],
            'portal_access' => ['sometimes', 'boolean'],
        ];
    }
}
