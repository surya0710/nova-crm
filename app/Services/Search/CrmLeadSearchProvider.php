<?php

namespace App\Services\Search;

use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Services\LeadService;
use App\Services\LeadVisibilityService;
use App\Services\MetadataSearchService;
use Illuminate\Support\Collection;

class CrmLeadSearchProvider implements SearchProviderInterface
{
    public function __construct(
        protected MetadataSearchService $metadataSearch,
        protected LeadService $leadService,
        protected LeadVisibilityService $leadVisibility,
    ) {}

    public function key(): string
    {
        return 'leads';
    }

    public function label(): string
    {
        return function_exists('crm_term') ? crm_term('leads') : __('Leads');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasPermission('leads.view')) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        return $this->leadVisibility->visibleQuery($user, $organization)
            ->where('leads.organization_id', $organization->id)
            ->where(function ($q) use ($query, $organization) {
                $this->leadService->searchQuery($q, $query);
                $this->metadataSearch->applySearchConstraint($q, 'lead', $query, $organization->id);
            })
            ->limit($limit)
            ->get()
            ->map(fn (Lead $lead) => [
                'type' => function_exists('crm_term') ? crm_term('lead') : __('Lead'),
                'label' => $this->label(),
                'title' => $lead->name,
                'subtitle' => $lead->company,
                'url' => route('leads.show', $lead),
                'workspace' => 'crm',
            ]);
    }
}
