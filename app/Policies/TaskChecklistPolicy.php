<?php

namespace App\Policies;

use App\Models\TaskChecklist;
use App\Models\User;

class TaskChecklistPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tasks.view');
    }

    public function view(User $user, TaskChecklist $checklist): bool
    {
        return $user->hasPermission('tasks.view', $checklist->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tasks.manage-checklists');
    }

    public function update(User $user, TaskChecklist $checklist): bool
    {
        return $user->hasPermission('tasks.manage-checklists', $checklist->organization);
    }

    public function delete(User $user, TaskChecklist $checklist): bool
    {
        return $user->hasPermission('tasks.manage-checklists', $checklist->organization);
    }
}
