<?php

namespace App\Policies;

use App\Models\ProjectTemplate;
use App\Models\User;

class ProjectTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.templates.view')
            || $user->hasPermission('projects.templates.manage');
    }

    public function view(User $user, ProjectTemplate $template): bool
    {
        if ($template->organization_id === null && $template->is_system) {
            return $user->hasPermission('projects.templates.view')
                || $user->hasPermission('projects.templates.manage');
        }

        $organization = $template->organization;

        return $user->hasPermission('projects.templates.view', $organization)
            || $user->hasPermission('projects.templates.manage', $organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.templates.create')
            || $user->hasPermission('projects.templates.manage');
    }

    public function update(User $user, ProjectTemplate $template): bool
    {
        if ($template->organization_id === null && $template->is_system) {
            return $user->hasPermission('projects.templates.update')
                || $user->hasPermission('projects.templates.manage');
        }

        $organization = $template->organization;

        return $user->hasPermission('projects.templates.update', $organization)
            || $user->hasPermission('projects.templates.manage', $organization);
    }

    public function delete(User $user, ProjectTemplate $template): bool
    {
        if ($template->organization_id === null && $template->is_system) {
            return false;
        }

        $organization = $template->organization;

        return $user->hasPermission('projects.templates.delete', $organization)
            || $user->hasPermission('projects.templates.manage', $organization);
    }
}
