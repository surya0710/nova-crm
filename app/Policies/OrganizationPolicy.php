<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function viewSettings(User $user, Organization $organization): bool
    {
        return $user->hasPermission('settings.manage', $organization);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->hasPermission('settings.manage', $organization);
    }
}
