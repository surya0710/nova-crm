<?php

namespace App\Policies;

use App\Models\HrmsTeam;
use App\Models\User;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('hrms.view');
    }

    public function view(User $user, HrmsTeam $team): bool
    {
        return $user->hasPermission('hrms.view', $team->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('hrms.create');
    }

    public function update(User $user, HrmsTeam $team): bool
    {
        return $user->hasPermission('hrms.update', $team->organization);
    }

    public function delete(User $user, HrmsTeam $team): bool
    {
        return $user->hasPermission('hrms.manage', $team->organization);
    }

    public function manage(User $user, HrmsTeam $team): bool
    {
        return $user->hasPermission('hrms.manage', $team->organization);
    }
}
