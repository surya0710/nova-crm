<?php

namespace App\Policies;

use App\Models\ProjectMember;
use App\Models\User;

class ProjectMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.view');
    }

    public function view(User $user, ProjectMember $member): bool
    {
        return $user->hasPermission('projects.view', $member->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.assign-members');
    }

    public function update(User $user, ProjectMember $member): bool
    {
        return $user->hasPermission('projects.assign-members', $member->organization);
    }

    public function delete(User $user, ProjectMember $member): bool
    {
        return $user->hasPermission('projects.assign-members', $member->organization);
    }
}
