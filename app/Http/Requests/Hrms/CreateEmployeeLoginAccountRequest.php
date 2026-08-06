<?php

namespace App\Http\Requests\Hrms;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEmployeeLoginAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Employee|null $employee */
        $employee = $this->route('employee');

        return $employee && ($this->user()?->can('manage', $employee) ?? false);
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['nullable', 'string', Rule::in(array_keys(config('rbac.roles', [])))],
            'send_invitation' => ['sometimes', 'boolean'],
            'portal_access' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        /** @var Employee|null $employee */
        $employee = $this->route('employee');

        if ($employee && ! $this->filled('email') && $employee->email) {
            $this->merge(['email' => $employee->email]);
        }
        if ($employee && ! $this->filled('name')) {
            $this->merge(['name' => $employee->full_name]);
        }
        if (! $this->has('send_invitation')) {
            $this->merge(['send_invitation' => true]);
        }
        if (! $this->has('portal_access')) {
            $this->merge(['portal_access' => true]);
        }
    }
}
