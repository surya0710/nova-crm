<?php

namespace App\Policies;

use App\Models\SavedFilter;
use App\Models\User;
use App\Services\TenantContext;

class SavedFilterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('leads.view')
            || $user->hasPermission('customers.view')
            || $user->hasPermission('opportunities.view');
    }

    public function view(User $user, SavedFilter $filter): bool
    {
        if (! $this->belongsToCurrentTenant($filter)) {
            return false;
        }

        if (! $this->canAccessEntity($user, $filter)) {
            return false;
        }

        if ($filter->isShared()) {
            return true;
        }

        return $filter->isOwnedBy($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, SavedFilter $filter): bool
    {
        if (! $this->belongsToCurrentTenant($filter)) {
            return false;
        }

        if (! $this->canAccessEntity($user, $filter)) {
            return false;
        }

        if ($filter->isOwnedBy($user)) {
            return true;
        }

        return $filter->isShared() && $user->hasPermission('metadata.manage', $filter->organization);
    }

    public function delete(User $user, SavedFilter $filter): bool
    {
        return $this->update($user, $filter);
    }

    public function duplicate(User $user, SavedFilter $filter): bool
    {
        return $this->view($user, $filter);
    }

    protected function canAccessEntity(User $user, SavedFilter $filter): bool
    {
        return match ($filter->entity_type) {
            'lead' => $user->hasPermission('leads.view', $filter->organization),
            'customer' => $user->hasPermission('customers.view', $filter->organization),
            'opportunity' => $user->hasPermission('opportunities.view', $filter->organization),
            default => false,
        };
    }

    protected function belongsToCurrentTenant(SavedFilter $filter): bool
    {
        $organization = app(TenantContext::class)->get();

        if (! $organization) {
            return false;
        }

        return (int) $filter->organization_id === (int) $organization->id;
    }
}
