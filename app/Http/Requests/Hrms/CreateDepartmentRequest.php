<?php

namespace App\Http\Requests\Hrms;

use App\Models\Department;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Department::class) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('hrms_departments', 'code')->where('organization_id', $org?->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'branch_id' => ['nullable', Rule::exists('hrms_branches', 'id')],
            'parent_id' => ['nullable', Rule::exists('hrms_departments', 'id')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
