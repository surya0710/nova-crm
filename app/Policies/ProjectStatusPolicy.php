<?php

namespace App\Policies;

use App\Models\ProjectStatus;
use App\Models\User;

class ProjectStatusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.view');
    }

    public function view(User $user, ProjectStatus $status): bool
    {
        return $user->hasPermission('projects.view', $status->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.manage');
    }

    public function update(User $user, ProjectStatus $status): bool
    {
        return $user->hasPermission('projects.manage', $status->organization);
    }

    public function delete(User $user, ProjectStatus $status): bool
    {
        return $user->hasPermission('projects.manage', $status->organization);
    }
}
