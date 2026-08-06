<?php

namespace App\Policies;

use App\Models\ProjectDependency;
use App\Models\User;

class ProjectDependencyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.dependencies.view')
            || $user->hasPermission('projects.dependencies.manage');
    }

    public function view(User $user, ProjectDependency $dependency): bool
    {
        return $user->hasPermission('projects.dependencies.view', $dependency->organization)
            || $user->hasPermission('projects.dependencies.manage', $dependency->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.dependencies.manage');
    }

    public function update(User $user, ProjectDependency $dependency): bool
    {
        return $user->hasPermission('projects.dependencies.manage', $dependency->organization);
    }

    public function delete(User $user, ProjectDependency $dependency): bool
    {
        return $user->hasPermission('projects.dependencies.manage', $dependency->organization);
    }
}
