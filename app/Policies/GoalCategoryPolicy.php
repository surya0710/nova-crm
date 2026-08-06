<?php

namespace App\Policies;

use App\Models\GoalCategory;
use App\Models\User;

class GoalCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('performance.goal.view');
    }

    public function view(User $user, GoalCategory $goalCategory): bool
    {
        return $user->hasPermission('performance.goal.view', $goalCategory->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('performance.goal.manage');
    }

    public function update(User $user, GoalCategory $goalCategory): bool
    {
        return $user->hasPermission('performance.goal.manage', $goalCategory->organization);
    }

    public function delete(User $user, GoalCategory $goalCategory): bool
    {
        return $user->hasPermission('performance.goal.manage', $goalCategory->organization);
    }
}
