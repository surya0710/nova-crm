<?php

namespace App\Policies;

use App\Models\TaskPriority;
use App\Models\User;

class TaskPriorityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tasks.view');
    }

    public function view(User $user, TaskPriority $priority): bool
    {
        return $user->hasPermission('tasks.view', $priority->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tasks.manage-priority')
            || $user->hasPermission('tasks.manage');
    }

    public function update(User $user, TaskPriority $priority): bool
    {
        return $user->hasPermission('tasks.manage-priority', $priority->organization)
            || $user->hasPermission('tasks.manage', $priority->organization);
    }

    public function delete(User $user, TaskPriority $priority): bool
    {
        return $user->hasPermission('tasks.manage-priority', $priority->organization)
            || $user->hasPermission('tasks.manage', $priority->organization);
    }
}
