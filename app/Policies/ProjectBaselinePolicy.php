<?php

namespace App\Policies;

use App\Models\ProjectBaseline;
use App\Models\User;

class ProjectBaselinePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.baselines.view')
            || $user->hasPermission('projects.baselines.manage');
    }

    public function view(User $user, ProjectBaseline $baseline): bool
    {
        return $user->hasPermission('projects.baselines.view', $baseline->organization)
            || $user->hasPermission('projects.baselines.manage', $baseline->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.baselines.create')
            || $user->hasPermission('projects.baselines.manage');
    }

    public function update(User $user, ProjectBaseline $baseline): bool
    {
        return $user->hasPermission('projects.baselines.manage', $baseline->organization);
    }

    public function delete(User $user, ProjectBaseline $baseline): bool
    {
        return $user->hasPermission('projects.baselines.manage', $baseline->organization);
    }
}
