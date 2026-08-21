<?php

namespace App\Policies;

use App\Models\CrmActivity;
use App\Models\User;

class CrmActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('customers.view')
            || $user->hasPermission('leads.view')
            || $user->hasPermission('opportunities.view');
    }

    public function view(User $user, CrmActivity $activity): bool
    {
        return $user->hasPermission('customers.view', $activity->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('customers.update') || $user->hasPermission('customers.create');
    }

    public function update(User $user, CrmActivity $activity): bool
    {
        return $user->hasPermission('customers.update', $activity->organization);
    }

    public function delete(User $user, CrmActivity $activity): bool
    {
        return $user->hasPermission('customers.update', $activity->organization)
            || $user->hasPermission('customers.delete', $activity->organization);
    }
}
