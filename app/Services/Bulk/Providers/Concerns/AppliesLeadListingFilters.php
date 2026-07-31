<?php

namespace App\Services\Bulk\Providers\Concerns;

use App\Services\LeadService;
use App\Services\MetadataQueryDefinitionService;
use App\Services\MetadataQueryService;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

trait AppliesLeadListingFilters
{
    /**
     * Mirror LeadController::index filters for bulk "all filtered" selection.
     *
     * @param  Builder<\App\Models\Lead>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        /** @var LeadService $leads */
        $leads = app(LeadService::class);

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

        if ($assignedTo = (int) Arr::get($filters, 'assigned_to', 0)) {
            $query->where('leads.assigned_to', $assignedTo);
        }

        $organization = app(TenantContext::class)->get();
        if (! $organization) {
            return;
        }

        $definitions = app(MetadataQueryDefinitionService::class);
        $metadataQueries = app(MetadataQueryService::class);
        $metadataRequest = $definitions->requestForWebIndex($organization->id, 'lead', $filters);
        $metadataQueries->applyForWebIndex($query, $metadataRequest, $organization->id);
    }
}
