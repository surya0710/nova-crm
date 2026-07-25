<?php

namespace App\Policies;

use App\Models\CompetencyCategory;
use App\Models\User;

class CompetencyCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('performance.view');
    }

    public function view(User $user, CompetencyCategory $category): bool
    {
        return $user->hasPermission('performance.view', $category->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('performance.manage');
    }

    public function update(User $user, CompetencyCategory $category): bool
    {
        return $user->hasPermission('performance.manage', $category->organization);
    }

    public function delete(User $user, CompetencyCategory $category): bool
    {
        return $user->hasPermission('performance.manage', $category->organization);
    }
}
