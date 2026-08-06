<?php

namespace App\Services\Search;

use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\User;
use App\Services\MetadataSearchService;
use Illuminate\Support\Collection;

class CrmOpportunitySearchProvider implements SearchProviderInterface
{
    public function __construct(protected MetadataSearchService $metadataSearch) {}

    public function key(): string
    {
        return 'opportunities';
    }

    public function label(): string
    {
        return function_exists('crm_term') ? crm_term('pipeline') : __('Opportunities');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasPermission('opportunities.view')) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        return Opportunity::query()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%");

                $this->metadataSearch->applySearchConstraint($q, 'opportunity', $query);
            })
            ->limit($limit)
            ->get()
            ->map(fn (Opportunity $opportunity) => [
                'type' => function_exists('crm_term') ? crm_term('deal') : __('Opportunity'),
                'label' => $this->label(),
                'title' => $opportunity->title,
                'subtitle' => $opportunity->stage_label,
                'url' => route('pipeline.show', $opportunity),
                'workspace' => 'crm',
            ]);
    }
}
