<?php

namespace App\Policies;

use App\Models\ProjectMilestone;
use App\Models\User;

class ProjectMilestonePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.view');
    }

    public function view(User $user, ProjectMilestone $milestone): bool
    {
        return $user->hasPermission('projects.view', $milestone->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.manage-milestones');
    }

    public function update(User $user, ProjectMilestone $milestone): bool
    {
        return $user->hasPermission('projects.manage-milestones', $milestone->organization);
    }

    public function delete(User $user, ProjectMilestone $milestone): bool
    {
        return $user->hasPermission('projects.manage-milestones', $milestone->organization);
    }

    public function complete(User $user, ProjectMilestone $milestone): bool
    {
        return $user->hasPermission('projects.manage-milestones', $milestone->organization);
    }
}
