<?php

namespace App\Policies;

use App\Models\ProjectLifecycleStage;
use App\Models\User;

class ProjectLifecycleStagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.view');
    }

    public function view(User $user, ProjectLifecycleStage $stage): bool
    {
        return $user->hasPermission('projects.view', $stage->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.manage');
    }

    public function update(User $user, ProjectLifecycleStage $stage): bool
    {
        return $user->hasPermission('projects.manage', $stage->organization);
    }

    public function delete(User $user, ProjectLifecycleStage $stage): bool
    {
        return $user->hasPermission('projects.manage', $stage->organization);
    }
}
