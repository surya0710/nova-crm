<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadVisibilityService;

class LeadPolicy
{
    public function __construct(
        protected LeadVisibilityService $visibility,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('leads.view');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $this->visibility->canAccess($user, $lead);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('leads.create');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->hasPermission('leads.update', $lead->organization)
            && $this->visibility->canAccess($user, $lead);
    }

    public function convert(User $user, Lead $lead): bool
    {
        return $user->hasPermission('leads.update', $lead->organization)
            && $user->hasPermission('customers.create', $lead->organization)
            && $this->visibility->canAccess($user, $lead);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->hasPermission('leads.delete', $lead->organization)
            && $this->visibility->canAccess($user, $lead);
    }
}
