<?php

namespace App\Services\Bulk\Providers\Concerns;

use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Services\LeadService;
use App\Services\LeadVisibilityService;
use App\Services\MetadataQueryDefinitionService;
use App\Services\MetadataQueryService;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

trait AppliesLeadListingFilters
{
    /**
     * Organization + selection query with lead visibility always applied.
     *
     * @param  array{mode?: string, ids?: list<int|string>, filters?: array<string, mixed>}  $selection
     * @return Builder<Lead>
     */
    protected function resolveLeadQuery(Organization $organization, array $selection, ?User $actor = null): Builder
    {
        $query = $this->baseOrganizationQuery(Lead::class, $organization, $selection);
        $actor ??= auth()->user();

        if ($actor instanceof User) {
            app(LeadVisibilityService::class)->apply($query, $actor, $organization);
        }

        return $query;
    }

    /**
     * Mirror LeadController::index filters for bulk "all filtered" selection.
     *
     * @param  Builder<Lead>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        /** @var LeadService $leads */
        $leads = app(LeadService::class);
        $visibility = app(LeadVisibilityService::class);
        $organization = app(TenantContext::class)->get();
        $actor = auth()->user();

        $leads->searchQuery($query, Arr::get($filters, 'search'));
        $leads->geographicFilterQuery(
            $query,
            Arr::get($filters, 'state'),
            Arr::get($filters, 'country'),
        );

        if ($status = Arr::get($filters, 'status')) {
            $query->where('leads.status', $status);
        }

        if ($source = Arr::get($filters, 'source')) {
            $query->where('leads.source', $source);
        }

        if ($priority = Arr::get($filters, 'priority')) {
            $query->where('leads.priority', $priority);
        }

        if ($actor instanceof User) {
            $assignedTo = $visibility->resolveAssignedToFilter(
                $actor,
                $organization,
                Arr::get($filters, 'assigned_to'),
            );
            if ($visibility->canViewAll($actor, $organization) && $assignedTo !== null) {
                $query->where('leads.assigned_to', $assignedTo);
            }
        }

        if (! $organization) {
            return;
        }

        $definitions = app(MetadataQueryDefinitionService::class);
        $metadataQueries = app(MetadataQueryService::class);
        $metadataRequest = $definitions->requestForWebIndex($organization->id, 'lead', $filters);
        $metadataQueries->applyForWebIndex($query, $metadataRequest, $organization->id);
    }
}
