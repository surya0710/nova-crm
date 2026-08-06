<?php

namespace App\Policies;

use App\Models\TaskStatus;
use App\Models\User;

class TaskStatusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tasks.view');
    }

    public function view(User $user, TaskStatus $status): bool
    {
        return $user->hasPermission('tasks.view', $status->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tasks.manage-status')
            || $user->hasPermission('tasks.manage');
    }

    public function update(User $user, TaskStatus $status): bool
    {
        return $user->hasPermission('tasks.manage-status', $status->organization)
            || $user->hasPermission('tasks.manage', $status->organization);
    }

    public function delete(User $user, TaskStatus $status): bool
    {
        return $user->hasPermission('tasks.manage-status', $status->organization)
            || $user->hasPermission('tasks.manage', $status->organization);
    }
}
