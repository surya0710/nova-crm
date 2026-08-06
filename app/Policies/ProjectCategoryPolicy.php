<?php

namespace App\Policies;

use App\Models\ProjectCategory;
use App\Models\User;

class ProjectCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.view');
    }

    public function view(User $user, ProjectCategory $category): bool
    {
        return $user->hasPermission('projects.view', $category->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.manage');
    }

    public function update(User $user, ProjectCategory $category): bool
    {
        return $user->hasPermission('projects.manage', $category->organization);
    }

    public function delete(User $user, ProjectCategory $category): bool
    {
        return $user->hasPermission('projects.manage', $category->organization);
    }
}
