<?php

namespace App\Http\Requests\Hrms;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('department')) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();
        $department = $this->route('department');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('hrms_departments', 'code')->where('organization_id', $org?->id)->ignore($department?->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'branch_id' => ['nullable', Rule::exists('hrms_branches', 'id')],
            'parent_id' => ['nullable', Rule::exists('hrms_departments', 'id')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
