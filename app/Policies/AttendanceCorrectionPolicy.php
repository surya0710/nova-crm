<?php

namespace App\Policies;

use App\Models\AttendanceCorrection;
use App\Models\User;

class AttendanceCorrectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('attendance.view')
            || $user->hasPermission('attendance.correct');
    }

    public function view(User $user, AttendanceCorrection $correction): bool
    {
        return $user->hasPermission('attendance.view', $correction->organization)
            || $user->hasPermission('attendance.correct', $correction->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('attendance.manage')
            || $user->hasPermission('attendance.correct');
    }

    public function review(User $user, AttendanceCorrection $correction): bool
    {
        return $user->hasPermission('attendance.correct', $correction->organization);
    }
}
