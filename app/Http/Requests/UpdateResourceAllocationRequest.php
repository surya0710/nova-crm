<?php

namespace App\Http\Requests;

use App\Models\ResourceAllocation;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResourceAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $allocation = $this->route('allocation') ?? $this->route('resource_allocation');

        return $allocation instanceof ResourceAllocation
            ? ($this->user()?->can('update', $allocation) ?? false)
            : false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = app(TenantContext::class)->id();
        $types = array_keys(config('resources.allocation_types', []));
        $max = (int) config('resources.max_allocation_percentage', 100);

        return [
            'employee_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('employees', 'id')->where('organization_id', $organizationId),
            ],
            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where('organization_id', $organizationId),
            ],
            'task_id' => [
                'nullable',
                'integer',
                Rule::exists('tasks', 'id')->where('organization_id', $organizationId),
            ],
            'allocation_type' => ['sometimes', 'required', 'string', Rule::in($types)],
            'allocation_percentage' => ['sometimes', 'required', 'integer', 'min:1', 'max:'.$max],
            'planned_hours' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'planned_start_date' => ['sometimes', 'required', 'date'],
            'planned_end_date' => ['sometimes', 'required', 'date', 'after_or_equal:planned_start_date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['project_id', 'task_id', 'planned_hours', 'notes'] as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
