<?php

namespace App\Http\Requests\Hrms;

use App\Models\Goal;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Goal::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $org = app(TenantContext::class)->get();
        $assigneeType = $this->input('assignee_type', 'employee');

        return [
            'performance_cycle_id' => [
                'required', 'integer',
                Rule::exists('performance_cycles', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'goal_template_id' => [
                'nullable', 'integer',
                Rule::exists('goal_templates', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'kpi_id' => [
                'nullable', 'integer',
                Rule::exists('kpis', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'goal_category_id' => [
                'nullable', 'integer',
                Rule::exists('goal_categories', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'goal_type' => ['nullable', 'string', Rule::in(array_keys(config('hrms.goal_types', [])))],
            'assignee_type' => ['required', 'string', Rule::in(array_keys(config('hrms.goal_assignee_types', [])))],
            'employee_id' => [
                Rule::requiredIf($assigneeType === 'employee'),
                'nullable', 'integer',
                Rule::exists('employees', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'team_id' => [
                Rule::requiredIf($assigneeType === 'team'),
                'nullable', 'integer',
                Rule::exists('hrms_teams', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'department_id' => [
                Rule::requiredIf($assigneeType === 'department'),
                'nullable', 'integer',
                Rule::exists('hrms_departments', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'measurement_type' => ['nullable', 'string', Rule::in(array_keys(config('hrms.goal_measurement_types', [])))],
            'target_value' => ['nullable', 'numeric'],
            'current_value' => ['nullable', 'numeric'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(array_keys(config('hrms.goal_statuses', [])))],
        ];
    }
}
