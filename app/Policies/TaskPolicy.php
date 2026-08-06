<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Services\TaskAuthorizationService;

class TaskPolicy
{
    public function __construct(protected TaskAuthorizationService $auth) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tasks.view');
    }

    public function view(User $user, Task $task): bool
    {
        return $this->auth->canView($user, $task);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tasks.create');
    }

    public function update(User $user, Task $task): bool
    {
        return $this->auth->canFullyUpdate($user, $task);
    }

    public function updateOwnWork(User $user, Task $task): bool
    {
        return $this->auth->canUpdateOwnWork($user, $task);
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
        return $this->auth->canComment($user, $task);
    }

    public function attachments(User $user, Task $task): bool
    {
        return $this->auth->canManageAttachments($user, $task);
    }

    public function timeLog(User $user, Task $task): bool
    {
        return $this->auth->canLogTime($user, $task);
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
        return $this->auth->canManageChecklists($user, $task);
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
