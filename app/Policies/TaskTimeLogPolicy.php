<?php

namespace App\Policies;

use App\Models\TaskTimeLog;
use App\Models\User;

class TaskTimeLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tasks.view');
    }

    public function view(User $user, TaskTimeLog $timeLog): bool
    {
        return $user->hasPermission('tasks.view', $timeLog->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tasks.time-log');
    }

    public function update(User $user, TaskTimeLog $timeLog): bool
    {
        return $user->hasPermission('tasks.time-log', $timeLog->organization);
    }

    public function delete(User $user, TaskTimeLog $timeLog): bool
    {
        return $user->hasPermission('tasks.time-log', $timeLog->organization);
    }
}
