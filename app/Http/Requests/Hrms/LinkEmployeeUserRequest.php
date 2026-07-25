<?php

namespace App\Http\Requests\Hrms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkEmployeeUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('hrms.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required_without:create_user', Rule::exists('users', 'id')],
            'create_user' => ['nullable', 'boolean'],
            'name' => ['required_if:create_user,1', 'string', 'max:255'],
            'email' => ['required_if:create_user,1', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['nullable', 'string', Rule::in(array_keys(config('rbac.roles', [])))],
        ];
    }
}
