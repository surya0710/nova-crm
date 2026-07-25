<?php

namespace App\Policies;

use App\Models\ProjectIssue;
use App\Models\User;

class ProjectIssuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.issues.view')
            || $user->hasPermission('projects.issues.manage');
    }

    public function view(User $user, ProjectIssue $issue): bool
    {
        return $user->hasPermission('projects.issues.view', $issue->organization)
            || $user->hasPermission('projects.issues.manage', $issue->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.issues.create')
            || $user->hasPermission('projects.issues.manage');
    }

    public function update(User $user, ProjectIssue $issue): bool
    {
        return $user->hasPermission('projects.issues.update', $issue->organization)
            || $user->hasPermission('projects.issues.manage', $issue->organization);
    }

    public function delete(User $user, ProjectIssue $issue): bool
    {
        return $user->hasPermission('projects.issues.delete', $issue->organization)
            || $user->hasPermission('projects.issues.manage', $issue->organization);
    }
}
