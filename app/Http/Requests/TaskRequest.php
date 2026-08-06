<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class TaskRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function baseRules(): array
    {
        $organization = app(TenantContext::class)->get();
        $organizationId = $organization?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['nullable', 'string', Rule::in(array_keys(config('tasks.statuses', [])))],
            'priority' => ['nullable', 'string', Rule::in(array_keys(config('tasks.priorities', [])))],
            'status_id' => [
                'nullable',
                'integer',
                Rule::exists('task_statuses', 'id')->where('organization_id', $organizationId),
            ],
            'priority_id' => [
                'nullable',
                'integer',
                Rule::exists('task_priorities', 'id')->where('organization_id', $organizationId),
            ],
            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where('organization_id', $organizationId),
            ],
            'parent_task_id' => [
                'nullable',
                'integer',
                Rule::exists('tasks', 'id')->where('organization_id', $organizationId),
            ],
            'milestone_id' => [
                'nullable',
                'integer',
                Rule::exists('project_milestones', 'id')->where('organization_id', $organizationId),
            ],
            'due_at' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'actual_hours' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'completion_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'slug' => ['nullable', 'string', 'max:255'],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $organizationId),
            ],
            'taskable_type' => ['nullable', 'string', Rule::in(array_keys(config('tasks.taskable', [])))],
            'taskable_id' => ['nullable', 'integer', 'min:1', 'required_with:taskable_type'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['due_at', 'start_date', 'due_date', 'estimated_hours', 'actual_hours', 'slug'] as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
