<?php

namespace App\Policies;

use App\Models\HrmsShift;
use App\Models\User;

class ShiftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('attendance.view');
    }

    public function view(User $user, HrmsShift $shift): bool
    {
        return $user->hasPermission('attendance.view', $shift->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('attendance.manage');
    }

    public function update(User $user, HrmsShift $shift): bool
    {
        return $user->hasPermission('attendance.manage', $shift->organization);
    }

    public function delete(User $user, HrmsShift $shift): bool
    {
        return $user->hasPermission('attendance.manage', $shift->organization);
    }
}
