<?php

namespace App\Policies;

use App\Models\ClientUser;
use App\Models\User;

class ClientUserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('portal.view')
            || $user->hasPermission('portal.manage')
            || $user->hasPermission('projects.view');
    }

    public function manage(User $user, ?ClientUser $client = null): bool
    {
        $org = $client?->organization;

        return $user->hasPermission('portal.manage', $org)
            || $user->hasPermission('projects.manage', $org);
    }
}
