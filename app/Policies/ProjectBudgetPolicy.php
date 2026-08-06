<?php

namespace App\Policies;

use App\Models\ProjectBudget;
use App\Models\User;

class ProjectBudgetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.budgets.view')
            || $user->hasPermission('projects.budgets.manage');
    }

    public function view(User $user, ProjectBudget $budget): bool
    {
        return $user->hasPermission('projects.budgets.view', $budget->organization)
            || $user->hasPermission('projects.budgets.manage', $budget->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.budgets.create')
            || $user->hasPermission('projects.budgets.manage');
    }

    public function update(User $user, ProjectBudget $budget): bool
    {
        return $user->hasPermission('projects.budgets.update', $budget->organization)
            || $user->hasPermission('projects.budgets.manage', $budget->organization);
    }

    public function delete(User $user, ProjectBudget $budget): bool
    {
        return $user->hasPermission('projects.budgets.manage', $budget->organization);
    }
}
