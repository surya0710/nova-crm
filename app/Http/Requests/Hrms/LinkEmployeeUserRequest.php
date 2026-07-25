<?php

namespace App\Http\Requests\Hrms;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkEmployeeUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        if ($employee instanceof Employee) {
            return $this->user()?->can('manage', $employee) ?? false;
        }

        return $this->user()?->hasPermission('hrms.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required_without:create_user', Rule::exists('users', 'id')],
            'create_user' => ['nullable', 'boolean'],
            'name' => ['required_if:create_user,1', 'nullable', 'string', 'max:255'],
            'email' => ['required_if:create_user,1', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['nullable', 'string', Rule::in(array_keys(config('rbac.roles', [])))],
            'send_invitation' => ['sometimes', 'boolean'],
            'portal_access' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('create_user') && ! $this->filled('user_id')) {
            $this->merge(['create_user' => true]);
        }
        if (! $this->has('send_invitation') && $this->boolean('create_user', true)) {
            $this->merge(['send_invitation' => true]);
        }
        if (! $this->has('portal_access') && $this->boolean('create_user', true)) {
            $this->merge(['portal_access' => true]);
        }
    }
}
