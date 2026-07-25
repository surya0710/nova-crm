<?php

namespace App\Policies;

use App\Models\ResourceAllocation;
use App\Models\User;

class ResourceAllocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('resources.view');
    }

    public function view(User $user, ResourceAllocation $allocation): bool
    {
        return $user->hasPermission('resources.view', $allocation->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('resources.allocate')
            || $user->hasPermission('resources.manage');
    }

    public function update(User $user, ResourceAllocation $allocation): bool
    {
        return $user->hasPermission('resources.allocate', $allocation->organization)
            || $user->hasPermission('resources.manage', $allocation->organization);
    }

    public function delete(User $user, ResourceAllocation $allocation): bool
    {
        return $user->hasPermission('resources.allocate', $allocation->organization)
            || $user->hasPermission('resources.manage', $allocation->organization);
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('resources.export');
    }
}
