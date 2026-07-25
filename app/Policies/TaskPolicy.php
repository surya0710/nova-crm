<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tasks.view');
    }

    public function view(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.view', $task->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tasks.create');
    }

    public function update(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.edit', $task->organization)
            || $user->hasPermission('tasks.update', $task->organization);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.delete', $task->organization);
    }

    public function archive(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.archive', $task->organization);
    }

    public function restore(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.restore', $task->organization);
    }

    public function assign(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.assign', $task->organization);
    }

    public function comment(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.comment', $task->organization);
    }

    public function attachments(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.attachments', $task->organization);
    }

    public function timeLog(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.time-log', $task->organization);
    }

    public function manageStatus(User $user): bool
    {
        return $user->hasPermission('tasks.manage-status');
    }

    public function managePriority(User $user): bool
    {
        return $user->hasPermission('tasks.manage-priority');
    }

    public function manageDependencies(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.manage-dependencies', $task->organization);
    }

    public function manageChecklists(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.manage-checklists', $task->organization);
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('tasks.export');
    }

    public function import(User $user): bool
    {
        return $user->hasPermission('tasks.import');
    }

    public function manage(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.manage', $task->organization);
    }
}
