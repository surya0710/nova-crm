<?php

namespace App\Policies;

use App\Models\HrmsAnnouncement;
use App\Models\User;

class HrmsAnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('hr.dashboard')
            || $user->hasPermission('announcements.manage')
            || $user->hasPermission('ess.access')
            || $user->hasPermission('manager.dashboard');
    }

    public function view(User $user, HrmsAnnouncement $announcement): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('announcements.manage');
    }

    public function update(User $user, HrmsAnnouncement $announcement): bool
    {
        return $user->hasPermission('announcements.manage', $announcement->organization);
    }

    public function delete(User $user, HrmsAnnouncement $announcement): bool
    {
        return $user->hasPermission('announcements.manage', $announcement->organization);
    }
}
