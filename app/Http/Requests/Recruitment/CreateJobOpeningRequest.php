<?php

namespace App\Http\Requests\Recruitment;

use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateJobOpeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', JobOpening::class) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'job_requisition_id' => [
                'required', 'integer',
                Rule::exists('job_requisitions', 'id')->where('organization_id', $org?->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'department_id' => [
                'nullable', 'integer',
                Rule::exists('hrms_departments', 'id')->where('organization_id', $org?->id),
            ],
            'designation_id' => [
                'nullable', 'integer',
                Rule::exists('hrms_designations', 'id')->where('organization_id', $org?->id),
            ],
            'employment_type' => ['nullable', 'string', Rule::in(array_keys(config('hrms.employment_types', [])))],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'responsibilities' => ['nullable', 'string', 'max:10000'],
            'requirements' => ['nullable', 'string', 'max:10000'],
            'skills' => ['nullable', 'string', 'max:5000'],
            'salary_range_min' => ['nullable', 'numeric', 'min:0'],
            'salary_range_max' => ['nullable', 'numeric', 'min:0', 'gte:salary_range_min'],
            'experience' => ['nullable', 'string', 'max:255'],
            'education' => ['nullable', 'string', 'max:255'],
            'closing_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $requisitionId = $this->input('job_requisition_id');
            if (! $requisitionId) {
                return;
            }

            $requisition = JobRequisition::query()->find($requisitionId);
            if ($requisition && $requisition->status !== 'approved') {
                $validator->errors()->add('job_requisition_id', 'Openings can only be created from approved requisitions.');
            }
        });
    }
}
