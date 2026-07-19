<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class TaskService
{
    public function createFor(Model $subject, array $data, User $actor): Task
    {
        $this->assertTenant($subject, $actor);
        $this->validateData($data);
        $assigneeId = isset($data['assigned_to']) ? (int) $data['assigned_to'] : null;
        if ($assigneeId && ! $subject->organization->users()->whereKey($assigneeId)->exists()) {
            throw ValidationException::withMessages(['assigned_to' => 'The assignee is not an organization member.']);
        }

        return Task::query()->create([
            'organization_id' => $subject->organization_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'priority' => $data['priority'] ?? 'medium',
            'due_at' => $data['due_at'] ?? null,
            'assigned_to' => $assigneeId,
            'taskable_type' => $subject->getMorphClass(),
            'taskable_id' => $subject->getKey(),
            'created_by' => $actor->id,
            'completed_at' => ($data['status'] ?? null) === 'completed' ? now() : null,
        ]);
    }

    public function create(array $data, User $actor, ?Model $subject = null): Task
    {
        if ($subject) {
            return $this->createFor($subject, $data, $actor);
        }

        $this->validateData($data);

        return Task::query()->create([
            ...$data,
            'created_by' => $actor->id,
            'completed_at' => ($data['status'] ?? null) === 'completed' ? now() : null,
        ]);
    }

    protected function assertTenant(Model $subject, User $actor): void
    {
        if (! $subject->organization_id || ! $subject->organization->users()->whereKey($actor->id)->exists()) {
            throw ValidationException::withMessages(['actor' => 'The actor does not belong to the subject organization.']);
        }
    }

    protected function validateData(array $data): void
    {
        if (trim((string) ($data['title'] ?? '')) === '') {
            throw ValidationException::withMessages(['title' => 'A task title is required.']);
        }
        if (! array_key_exists($data['status'] ?? 'pending', config('tasks.statuses', []))) {
            throw ValidationException::withMessages(['status' => 'Invalid task status.']);
        }
        if (! array_key_exists($data['priority'] ?? 'medium', config('tasks.priorities', []))) {
            throw ValidationException::withMessages(['priority' => 'Invalid task priority.']);
        }
    }
}
