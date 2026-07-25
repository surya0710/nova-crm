<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Project::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();
        $organizationId = $organization?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'objective' => ['nullable', 'string', 'max:5000'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('project_categories', 'id')->where('organization_id', $organizationId),
            ],
            'project_type_id' => [
                'nullable',
                'integer',
                Rule::exists('project_types', 'id')->where('organization_id', $organizationId),
            ],
            'status_id' => [
                'nullable',
                'integer',
                Rule::exists('project_statuses', 'id')->where('organization_id', $organizationId),
            ],
            'lifecycle_stage_id' => [
                'nullable',
                'integer',
                Rule::exists('project_lifecycle_stages', 'id')->where('organization_id', $organizationId),
            ],
            'client_id' => [
                'nullable',
                'integer',
                Rule::exists('customers', 'id')->where('organization_id', $organizationId),
            ],
            'owner_id' => [
                'required',
                'integer',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $organizationId),
            ],
            'manager_id' => [
                'required',
                'integer',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $organizationId),
            ],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('hrms_departments', 'id')->where('organization_id', $organizationId),
            ],
            'priority' => ['nullable', 'string', Rule::in(array_keys(config('projects.priorities')))],
            'start_date' => ['nullable', 'date'],
            'planned_end_date' => ['nullable', 'date'],
            'actual_end_date' => ['nullable', 'date'],
            'estimated_budget' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'actual_budget' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'completion_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('priority')) {
            $this->merge(['priority' => 'medium']);
        }

        foreach (['start_date', 'planned_end_date', 'actual_end_date'] as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
