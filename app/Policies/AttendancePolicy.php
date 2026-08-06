<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('attendance.view') || $user->hasPermission('ess.access');
    }

    public function view(User $user, AttendanceRecord $attendanceRecord): bool
    {
        if ($user->hasPermission('ess.access', $attendanceRecord->organization)
            && (int) $attendanceRecord->employee?->user_id === (int) $user->id) {
            return true;
        }

        return $user->hasPermission('attendance.view', $attendanceRecord->organization);
    }

    public function manage(User $user, ?AttendanceRecord $attendanceRecord = null): bool
    {
        $organization = $attendanceRecord?->organization;

        return $user->hasPermission('attendance.manage', $organization);
    }

    public function clock(User $user, Employee $employee): bool
    {
        return $user->hasPermission('ess.access', $employee->organization)
            && (int) $employee->user_id === (int) $user->id;
    }

    public function correct(User $user, ?AttendanceRecord $attendanceRecord = null): bool
    {
        $organization = $attendanceRecord?->organization;

        return $user->hasPermission('attendance.correct', $organization);
    }

    public function submitCorrection(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->hasPermission('ess.access', $attendanceRecord->organization)
            && (int) $attendanceRecord->employee?->user_id === (int) $user->id;
    }
}
