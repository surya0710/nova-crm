<?php

namespace App\Http\Requests\Hrms;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeCertificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');

        return $this->user()?->can('update', $employee) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'issuing_organization' => ['nullable', 'string', 'max:255'],
            'credential_number' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'credential_url' => ['nullable', 'url', 'max:500'],
            'status' => ['nullable', Rule::in(array_keys(config('hrms.certification_statuses')))],
        ];
    }
}
