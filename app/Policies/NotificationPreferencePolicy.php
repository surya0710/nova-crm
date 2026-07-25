<?php

namespace App\Policies;

use App\Models\NotificationPreference;
use App\Models\User;

class NotificationPreferencePolicy
{
    public function view(User $user, NotificationPreference $preference): bool
    {
        if ((int) $preference->user_id !== (int) $user->id) {
            return false;
        }

        return $user->hasPermission('projects.notifications.manage', $preference->organization);
    }

    public function update(User $user, NotificationPreference $preference): bool
    {
        if ((int) $preference->user_id !== (int) $user->id) {
            return false;
        }

        return $user->hasPermission('projects.notifications.manage', $preference->organization);
    }
}
