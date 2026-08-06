<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Models\WorkloadSnapshot;

class WorkloadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('resources.view');
    }

    public function view(User $user, WorkloadSnapshot $snapshot): bool
    {
        return $user->hasPermission('resources.view', $snapshot->organization);
    }

    public function viewOrganization(User $user, Organization $organization): bool
    {
        return $user->hasPermission('resources.view', $organization);
    }

    public function createSnapshot(User $user): bool
    {
        return $user->hasPermission('resources.manage');
    }
}
