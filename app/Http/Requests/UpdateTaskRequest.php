<?php

namespace App\Http\Requests;

use App\Services\TaskAuthorizationService;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends TaskRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task && (
            ($this->user()?->can('update', $task) ?? false)
            || ($this->user()?->can('updateOwnWork', $task) ?? false)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $task = $this->route('task');
        $user = $this->user();
        $auth = app(TaskAuthorizationService::class);

        if ($task && $user && $auth->canFullyUpdate($user, $task)) {
            return $this->baseRules();
        }

        $organizationId = $task?->organization_id;

        return [
            'status' => ['nullable', 'string', Rule::in(array_keys(config('tasks.statuses', [])))],
            'status_id' => [
                'nullable',
                'integer',
                Rule::exists('task_statuses', 'id')->where('organization_id', $organizationId),
            ],
            'completion_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $task = $this->route('task');
        $user = $this->user();
        $auth = app(TaskAuthorizationService::class);

        if (! $task || ! $user || $auth->canFullyUpdate($user, $task)) {
            return;
        }

        $allowed = array_flip($auth->assigneeEditableFields());
        $this->replace(array_intersect_key($this->all(), $allowed));
    }
}
