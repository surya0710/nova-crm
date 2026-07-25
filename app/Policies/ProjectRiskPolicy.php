<?php

namespace App\Policies;

use App\Models\ProjectRisk;
use App\Models\User;

class ProjectRiskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.risks.view')
            || $user->hasPermission('projects.risks.manage');
    }

    public function view(User $user, ProjectRisk $risk): bool
    {
        return $user->hasPermission('projects.risks.view', $risk->organization)
            || $user->hasPermission('projects.risks.manage', $risk->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.risks.create')
            || $user->hasPermission('projects.risks.manage');
    }

    public function update(User $user, ProjectRisk $risk): bool
    {
        return $user->hasPermission('projects.risks.update', $risk->organization)
            || $user->hasPermission('projects.risks.manage', $risk->organization);
    }

    public function delete(User $user, ProjectRisk $risk): bool
    {
        return $user->hasPermission('projects.risks.delete', $risk->organization)
            || $user->hasPermission('projects.risks.manage', $risk->organization);
    }
}
