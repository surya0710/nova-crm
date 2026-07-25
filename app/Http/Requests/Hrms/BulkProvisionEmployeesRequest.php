<?php

namespace App\Http\Requests\Hrms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkProvisionEmployeesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('hrms.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', Rule::exists('employees', 'id')],
            'role' => ['nullable', 'string', Rule::in(array_keys(config('rbac.roles', [])))],
            'send_invitation' => ['sometimes', 'boolean'],
            'portal_access' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('send_invitation')) {
            $this->merge(['send_invitation' => true]);
        }
        if (! $this->has('portal_access')) {
            $this->merge(['portal_access' => true]);
        }
    }
}
