<?php

namespace App\Policies;

use App\Models\Sprint;
use App\Models\User;

class SprintPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tasks.view') || $user->hasPermission('projects.view');
    }

    public function view(User $user, Sprint $sprint): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tasks.create') || $user->hasPermission('projects.edit') || $user->hasPermission('projects.manage');
    }

    public function update(User $user, Sprint $sprint): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Sprint $sprint): bool
    {
        return $user->hasPermission('tasks.delete') || $user->hasPermission('projects.manage');
    }
}
