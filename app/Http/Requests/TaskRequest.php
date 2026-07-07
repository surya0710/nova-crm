<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class TaskRequest extends FormRequest
{
    protected function baseRules(): array
    {
        $organization = app(TenantContext::class)->get();

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'string', Rule::in(array_keys(config('tasks.statuses')))],
            'priority' => ['required', 'string', Rule::in(array_keys(config('tasks.priorities')))],
            'due_at' => ['nullable', 'date'],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $organization?->id),
            ],
            'taskable_type' => ['nullable', 'string', Rule::in(array_keys(config('tasks.taskable')))],
            'taskable_id' => ['nullable', 'integer', 'min:1', 'required_with:taskable_type'],
        ];
    }
}
