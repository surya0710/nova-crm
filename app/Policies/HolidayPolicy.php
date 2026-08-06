<?php

namespace App\Policies;

use App\Models\Holiday;
use App\Models\User;

class HolidayPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('leave.view');
    }

    public function view(User $user, Holiday $holiday): bool
    {
        return $user->hasPermission('leave.view', $holiday->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('leave.manage');
    }

    public function update(User $user, Holiday $holiday): bool
    {
        return $user->hasPermission('leave.manage', $holiday->organization);
    }

    public function delete(User $user, Holiday $holiday): bool
    {
        return $user->hasPermission('leave.manage', $holiday->organization);
    }
}
