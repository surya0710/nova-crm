<?php

namespace App\Policies;

use App\Models\GoalTemplate;
use App\Models\User;

class GoalTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('performance.goal.view');
    }

    public function view(User $user, GoalTemplate $goalTemplate): bool
    {
        return $user->hasPermission('performance.goal.view', $goalTemplate->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('performance.goal.manage');
    }

    public function update(User $user, GoalTemplate $goalTemplate): bool
    {
        return $user->hasPermission('performance.goal.manage', $goalTemplate->organization);
    }

    public function delete(User $user, GoalTemplate $goalTemplate): bool
    {
        return $user->hasPermission('performance.goal.manage', $goalTemplate->organization);
    }
}
