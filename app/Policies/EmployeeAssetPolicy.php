<?php

namespace App\Policies;

use App\Models\EmployeeAsset;
use App\Models\User;

class EmployeeAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('assets.view');
    }

    public function view(User $user, EmployeeAsset $asset): bool
    {
        return $user->hasPermission('assets.view', $asset->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('assets.manage');
    }

    public function update(User $user, EmployeeAsset $asset): bool
    {
        return $user->hasPermission('assets.manage', $asset->organization);
    }

    public function delete(User $user, EmployeeAsset $asset): bool
    {
        return $user->hasPermission('assets.manage', $asset->organization);
    }

    public function assign(User $user, EmployeeAsset $asset): bool
    {
        return $user->hasPermission('assets.manage', $asset->organization);
    }

    public function return(User $user, EmployeeAsset $asset): bool
    {
        return $user->hasPermission('assets.manage', $asset->organization);
    }
}
