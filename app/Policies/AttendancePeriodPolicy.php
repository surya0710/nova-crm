<?php

namespace App\Policies;

use App\Models\AttendancePeriod;
use App\Models\User;

class AttendancePeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('attendance.view')
            || $user->hasPermission('attendance.lock');
    }

    public function view(User $user, AttendancePeriod $period): bool
    {
        return $user->hasPermission('attendance.view', $period->organization)
            || $user->hasPermission('attendance.lock', $period->organization);
    }

    public function manage(User $user, ?AttendancePeriod $period = null): bool
    {
        $organization = $period?->organization;

        return $user->hasPermission('attendance.lock', $organization)
            || $user->hasPermission('attendance.manage', $organization);
    }

    public function lock(User $user, ?AttendancePeriod $period = null): bool
    {
        return $this->manage($user, $period);
    }

    public function export(User $user, ?AttendancePeriod $period = null): bool
    {
        return $user->hasPermission('attendance.export', $period?->organization)
            || $user->hasPermission('attendance.view', $period?->organization);
    }
}
