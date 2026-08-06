<?php

namespace App\Policies;

use App\Models\TaskDependency;
use App\Models\User;

class TaskDependencyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tasks.view');
    }

    public function view(User $user, TaskDependency $dependency): bool
    {
        return $user->hasPermission('tasks.view', $dependency->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tasks.manage-dependencies');
    }

    public function update(User $user, TaskDependency $dependency): bool
    {
        return $user->hasPermission('tasks.manage-dependencies', $dependency->organization);
    }

    public function delete(User $user, TaskDependency $dependency): bool
    {
        return $user->hasPermission('tasks.manage-dependencies', $dependency->organization);
    }
}
