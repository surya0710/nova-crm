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
            'educations.*.end_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'educations.*.field_of_study' => ['nullable', 'string', 'max:255'],

            'experiences' => ['nullable', 'array'],
            'experiences.*.company' => ['required', 'string', 'max:255'],
            'experiences.*.title' => ['nullable', 'string', 'max:255'],
            'experiences.*.start_date' => ['nullable', 'date'],
            'experiences.*.end_date' => ['nullable', 'date'],
            'experiences.*.description' => ['nullable', 'string', 'max:5000'],

            'create_user' => ['sometimes', 'boolean'],
            'user_email' => ['nullable', 'email', 'max:255'],
            'role' => ['nullable', 'string', 'max:100'],
        ];
    }
}
