<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;

class ProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.programs.view')
            || $user->hasPermission('projects.programs.manage');
    }

    public function view(User $user, Program $program): bool
    {
        return $user->hasPermission('projects.programs.view', $program->organization)
            || $user->hasPermission('projects.programs.manage', $program->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.programs.create')
            || $user->hasPermission('projects.programs.manage');
    }

    public function update(User $user, Program $program): bool
    {
        return $user->hasPermission('projects.programs.update', $program->organization)
            || $user->hasPermission('projects.programs.manage', $program->organization);
    }

    public function delete(User $user, Program $program): bool
    {
        return $user->hasPermission('projects.programs.delete', $program->organization)
            || $user->hasPermission('projects.programs.manage', $program->organization);
    }

    public function attachProject(User $user, Program $program): bool
    {
        return $this->update($user, $program);
    }

    public function viewDashboard(User $user, Program $program): bool
    {
        return $this->view($user, $program);
    }
}
