<?php

namespace App\Policies;

use App\Models\Deliverable;
use App\Models\User;

class DeliverablePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, Deliverable $deliverable): bool
    {
        return $this->canView($user, $deliverable);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Deliverable $deliverable): bool
    {
        return $this->canManage($user, $deliverable);
    }

    public function delete(User $user, Deliverable $deliverable): bool
    {
        return $user->hasPermission('deliverable.manage', $deliverable->organization)
            || $user->hasPermission('portal.manage', $deliverable->organization)
            || $user->hasPermission('projects.manage', $deliverable->organization);
    }

    protected function canView(User $user, ?Deliverable $deliverable = null): bool
    {
        $org = $deliverable?->organization;

        return $user->hasPermission('portal.view', $org)
            || $user->hasPermission('portal.manage', $org)
            || $user->hasPermission('deliverable.manage', $org)
            || $user->hasPermission('projects.view', $org)
            || $user->hasPermission('projects.manage', $org);
    }

    protected function canManage(User $user, ?Deliverable $deliverable = null): bool
    {
        $org = $deliverable?->organization;

        return $user->hasPermission('deliverable.manage', $org)
            || $user->hasPermission('portal.manage', $org)
            || $user->hasPermission('projects.manage', $org)
            || $user->hasPermission('projects.edit', $org);
    }
}
