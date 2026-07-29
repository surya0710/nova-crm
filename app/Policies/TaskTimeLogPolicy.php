<?php

namespace App\Policies;

use App\Models\TaskTimeLog;
use App\Models\User;
use App\Services\TaskAuthorizationService;

class TaskTimeLogPolicy
{
    public function __construct(protected TaskAuthorizationService $auth) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tasks.view');
    }

    public function view(User $user, TaskTimeLog $timeLog): bool
    {
        $task = $timeLog->task;

        return $task !== null && $this->auth->canView($user, $task);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tasks.time-log')
            || $user->hasPermission('tasks.view');
    }

    public function update(User $user, TaskTimeLog $timeLog): bool
    {
        return $this->auth->canDeleteTimeLog($user, $timeLog);
    }

    public function delete(User $user, TaskTimeLog $timeLog): bool
    {
        return $this->auth->canDeleteTimeLog($user, $timeLog);
    }
}
