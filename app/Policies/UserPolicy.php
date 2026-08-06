<?php

namespace App\Policies;

use App\Models\User;
use App\Services\TenantContext;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.create');
    }

    public function update(User $user, User $member): bool
    {
        $organization = app(TenantContext::class)->get();

        if (! $organization || ! $member->belongsToOrganization($organization)) {
            return false;
        }

        return $user->hasPermission('users.update', $organization);
    }

    public function delete(User $user, User $member): bool
    {
        $organization = app(TenantContext::class)->get();

        if (! $organization || ! $member->belongsToOrganization($organization)) {
            return false;
        }

        if ($user->id === $member->id) {
            return false;
        }

        return $user->hasPermission('users.delete', $organization);
    }
}
