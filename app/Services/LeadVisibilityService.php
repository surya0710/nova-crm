<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Single source of truth for lead visibility (RBAC + assignee scope).
 *
 * - Owner / users with leads.manage: organization-wide (tenant scope only)
 * - Otherwise: only leads where assigned_to = current user
 * - Unassigned leads are not visible to restricted users
 */
class LeadVisibilityService
{
    public function canViewAll(User $user, ?Organization $organization): bool
    {
        return $user->hasPermission('leads.manage', $organization);
    }

    /**
     * @param  Builder<Lead>  $query
     * @return Builder<Lead>
     */
    public function apply(Builder $query, User $user, ?Organization $organization = null): Builder
    {
        if ($this->canViewAll($user, $organization)) {
            return $query;
        }

        return $query->where($query->getModel()->getTable().'.assigned_to', $user->id);
    }

    public function canAccess(User $user, Lead $lead): bool
    {
        $organization = $lead->organization;

        if (! $user->hasPermission('leads.view', $organization)) {
            return false;
        }

        if ($this->canViewAll($user, $organization)) {
            return true;
        }

        return $lead->assigned_to !== null
            && (int) $lead->assigned_to === (int) $user->id;
    }

    /**
     * Resolve the assignee filter for listing/export/bulk.
     * Restricted users always get their own id (client value ignored).
     * Managers get the requested id, or null when "anyone".
     */
    public function resolveAssignedToFilter(User $user, ?Organization $organization, mixed $requested): ?int
    {
        if (! $this->canViewAll($user, $organization)) {
            return (int) $user->id;
        }

        $id = (int) $requested;

        return $id > 0 ? $id : null;
    }

    /**
     * Preferred entry point for new lead queries.
     *
     * @return Builder<Lead>
     */
    public function visibleQuery(User $user, ?Organization $organization = null): Builder
    {
        return $this->apply(Lead::query(), $user, $organization);
    }
}
