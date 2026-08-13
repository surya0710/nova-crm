<?php

namespace App\Policies;

use App\Models\AttendanceGeofence;
use App\Models\User;

class AttendanceGeofencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('attendance.view');
    }

    public function view(User $user, AttendanceGeofence $geofence): bool
    {
        return $user->hasPermission('attendance.view', $geofence->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('attendance.manage');
    }

    public function update(User $user, AttendanceGeofence $geofence): bool
    {
        return $user->hasPermission('attendance.manage', $geofence->organization);
    }

    public function delete(User $user, AttendanceGeofence $geofence): bool
    {
        return $user->hasPermission('attendance.manage', $geofence->organization);
    }
}
