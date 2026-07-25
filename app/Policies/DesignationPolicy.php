<?php

namespace App\Policies;

use App\Models\Designation;
use App\Models\User;

class DesignationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('hrms.view');
    }

    public function view(User $user, Designation $designation): bool
    {
        return $user->hasPermission('hrms.view', $designation->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('hrms.create');
    }

    public function update(User $user, Designation $designation): bool
    {
        return $user->hasPermission('hrms.update', $designation->organization);
    }

    public function delete(User $user, Designation $designation): bool
    {
        return $user->hasPermission('hrms.manage', $designation->organization);
    }

    public function manage(User $user, Designation $designation): bool
    {
        return $user->hasPermission('hrms.manage', $designation->organization);
    }
}
