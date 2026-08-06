<?php

namespace App\Http\Requests\Hrms;

use App\Models\PerformanceReviewAssignment;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePerformanceReviewAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PerformanceReviewAssignment::class) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'performance_cycle_id' => [
                'required',
                'integer',
                Rule::exists('performance_cycles', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'review_template_id' => [
                'required',
                'integer',
                Rule::exists('performance_review_templates', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'primary_reviewer_id' => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'due_date' => ['nullable', 'date'],
            'review_type' => ['required', 'string', Rule::in(array_keys(config('hrms.performance_review_types', [])))],
            'status' => ['nullable', 'string', Rule::in(['planned', 'assigned'])],
        ];
    }
}
