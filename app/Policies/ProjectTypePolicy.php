<?php

namespace App\Policies;

use App\Models\ProjectType;
use App\Models\User;

class ProjectTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.view');
    }

    public function view(User $user, ProjectType $type): bool
    {
        return $user->hasPermission('projects.view', $type->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.manage');
    }

    public function update(User $user, ProjectType $type): bool
    {
        return $user->hasPermission('projects.manage', $type->organization);
    }

    public function delete(User $user, ProjectType $type): bool
    {
        return $user->hasPermission('projects.manage', $type->organization);
    }
}
