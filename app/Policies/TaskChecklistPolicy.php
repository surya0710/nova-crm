<?php

namespace App\Policies;

use App\Models\TaskChecklist;
use App\Models\User;
use App\Services\TaskAuthorizationService;

class TaskChecklistPolicy
{
    public function __construct(protected TaskAuthorizationService $auth) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tasks.view');
    }

    public function view(User $user, TaskChecklist $checklist): bool
    {
        $task = $checklist->task;

        return $task !== null && $this->auth->canView($user, $task);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tasks.manage-checklists')
            || $user->hasPermission('tasks.view');
    }

    public function update(User $user, TaskChecklist $checklist): bool
    {
        $task = $checklist->task;

        return $task !== null && $this->auth->canManageChecklists($user, $task);
    }

    public function delete(User $user, TaskChecklist $checklist): bool
    {
        $task = $checklist->task;

        return $task !== null && $this->auth->canManageChecklists($user, $task);
    }
}
