<?php

namespace App\Http\Requests\Hrms;

use App\Models\Designation;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateDesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Designation::class) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('hrms_designations', 'code')->where('organization_id', $org?->id)],
            'department_id' => ['nullable', Rule::exists('hrms_departments', 'id')],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
