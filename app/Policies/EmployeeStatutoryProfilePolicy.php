<?php

namespace App\Policies;

use App\Models\EmployeeStatutoryProfile;
use App\Models\User;

class EmployeeStatutoryProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.statutory.view')
            || $user->hasPermission('payroll.statutory.manage');
    }

    public function view(User $user, EmployeeStatutoryProfile $profile): bool
    {
        return $user->hasPermission('payroll.statutory.view', $profile->organization)
            || $user->hasPermission('payroll.statutory.manage', $profile->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payroll.statutory.manage');
    }

    public function update(User $user, EmployeeStatutoryProfile $profile): bool
    {
        return $user->hasPermission('payroll.statutory.manage', $profile->organization);
    }
}
