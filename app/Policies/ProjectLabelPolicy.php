<?php

namespace App\Policies;

use App\Models\ProjectLabel;
use App\Models\User;

class ProjectLabelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.labels.view')
            || $user->hasPermission('projects.labels.manage');
    }

    public function view(User $user, ProjectLabel $label): bool
    {
        return $user->hasPermission('projects.labels.view', $label->organization)
            || $user->hasPermission('projects.labels.manage', $label->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.labels.create')
            || $user->hasPermission('projects.labels.manage');
    }

    public function update(User $user, ProjectLabel $label): bool
    {
        return $user->hasPermission('projects.labels.update', $label->organization)
            || $user->hasPermission('projects.labels.manage', $label->organization);
    }

    public function delete(User $user, ProjectLabel $label): bool
    {
        return $user->hasPermission('projects.labels.delete', $label->organization)
            || $user->hasPermission('projects.labels.manage', $label->organization);
    }
}
