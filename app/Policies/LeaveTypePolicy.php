<?php

namespace App\Policies;

use App\Models\LeaveType;
use App\Models\User;

class LeaveTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('leave.view');
    }

    public function view(User $user, LeaveType $leaveType): bool
    {
        return $user->hasPermission('leave.view', $leaveType->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('leave.manage');
    }

    public function update(User $user, LeaveType $leaveType): bool
    {
        return $user->hasPermission('leave.manage', $leaveType->organization);
    }

    public function delete(User $user, LeaveType $leaveType): bool
    {
        return $user->hasPermission('leave.manage', $leaveType->organization);
    }
}
