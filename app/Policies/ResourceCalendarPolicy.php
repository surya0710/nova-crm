<?php

namespace App\Policies;

use App\Models\ResourceCalendar;
use App\Models\User;

class ResourceCalendarPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('resources.view');
    }

    public function view(User $user, ResourceCalendar $calendar): bool
    {
        return $user->hasPermission('resources.view', $calendar->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('resources.manage');
    }

    public function update(User $user, ResourceCalendar $calendar): bool
    {
        return $user->hasPermission('resources.manage', $calendar->organization);
    }

    public function delete(User $user, ResourceCalendar $calendar): bool
    {
        return $user->hasPermission('resources.manage', $calendar->organization);
    }
}
