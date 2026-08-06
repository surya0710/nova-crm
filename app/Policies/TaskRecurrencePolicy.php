<?php

namespace App\Policies;

use App\Models\TaskRecurrence;
use App\Models\User;

class TaskRecurrencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.recurrence.view')
            || $user->hasPermission('projects.recurrence.manage');
    }

    public function view(User $user, TaskRecurrence $recurrence): bool
    {
        return $user->hasPermission('projects.recurrence.view', $recurrence->organization)
            || $user->hasPermission('projects.recurrence.manage', $recurrence->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.recurrence.manage');
    }

    public function update(User $user, TaskRecurrence $recurrence): bool
    {
        return $user->hasPermission('projects.recurrence.manage', $recurrence->organization);
    }

    public function delete(User $user, TaskRecurrence $recurrence): bool
    {
        return $user->hasPermission('projects.recurrence.manage', $recurrence->organization);
    }
}
