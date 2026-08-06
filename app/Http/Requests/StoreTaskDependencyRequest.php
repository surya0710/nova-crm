<?php

namespace App\Http\Requests;

use App\Models\Task;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskDependencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task
            && ($this->user()?->can('manageDependencies', $task) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();
        $organizationId = $organization?->id;
        $task = $this->route('task');

        return [
            'predecessor_task_id' => [
                'required',
                'integer',
                Rule::exists('tasks', 'id')->where('organization_id', $organizationId),
                Rule::notIn([(int) ($task?->id ?? 0)]),
            ],
            'dependency_type' => ['nullable', 'string', Rule::in(array_keys(config('tasks.dependency_types', [])))],
        ];
    }
}
