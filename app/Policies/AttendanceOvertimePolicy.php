<?php

namespace App\Policies;

use App\Models\AttendanceOvertimeEntry;
use App\Models\AttendanceOvertimeRule;
use App\Models\User;

class AttendanceOvertimePolicy
{
    public function manage(User $user, AttendanceOvertimeRule|AttendanceOvertimeEntry|null $model = null): bool
    {
        return $this->manageRules($user, $model instanceof AttendanceOvertimeRule ? $model : null);
    }

    public function manageRules(User $user, ?AttendanceOvertimeRule $rule = null): bool
    {
        return $user->hasPermission('attendance.manage', $rule?->organization);
    }

    public function approve(User $user, AttendanceOvertimeEntry|AttendanceOvertimeRule|null $model = null): bool
    {
        return $this->approveOvertime(
            $user,
            $model instanceof AttendanceOvertimeEntry ? $model : null
        );
    }

    public function approveOvertime(User $user, ?AttendanceOvertimeEntry $entry = null): bool
    {
        $organization = $entry?->organization;

        return $user->hasPermission('attendance.approve', $organization)
            || $user->hasPermission('attendance.manage', $organization)
            || $user->hasPermission('attendance.correct', $organization);
    }

    public function rejectOvertime(User $user, ?AttendanceOvertimeEntry $entry = null): bool
    {
        return $this->approveOvertime($user, $entry);
    }

    public function bulkApproveOvertime(User $user): bool
    {
        return $this->approveOvertime($user);
    }

    public function bulkRejectOvertime(User $user): bool
    {
        return $this->rejectOvertime($user);
    }
}
