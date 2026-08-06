<?php

namespace App\Http\Requests\Recruitment;

use App\Models\JobRequisition;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $requisition = $this->route('job_requisition');

        return $requisition instanceof JobRequisition
            && ($this->user()?->can('update', $requisition) ?? false);
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'department_id' => [
                'required', 'integer',
                Rule::exists('hrms_departments', 'id')->where('organization_id', $org?->id),
            ],
            'designation_id' => [
                'required', 'integer',
                Rule::exists('hrms_designations', 'id')->where('organization_id', $org?->id),
            ],
            'employment_type' => ['required', 'string', Rule::in(array_keys(config('hrms.employment_types', [])))],
            'hiring_manager_id' => [
                'nullable', 'integer',
                Rule::exists('employees', 'id')->where('organization_id', $org?->id),
            ],
            'number_of_positions' => ['required', 'integer', 'min:1', 'max:999'],
            'business_justification' => ['required', 'string', 'max:5000'],
            'target_joining_date' => ['nullable', 'date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
