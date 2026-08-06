<?php

namespace App\Services\Search;

use App\Models\MarketingCampaign;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class MarketingCampaignSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'marketing_campaigns';
    }

    public function label(): string
    {
        return __('Campaigns');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasAnyPermission(['marketing.view', 'marketing.manage', 'integrations.view', 'integrations.manage'])) {
            return collect();
        }

        if (! Schema::hasTable('marketing_campaigns') || ! Route::has('marketing.campaigns.show')) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        return MarketingCampaign::query()
            ->where('organization_id', $organization->id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%'.$query.'%')
                    ->orWhere('slug', 'like', '%'.$query.'%')
                    ->orWhere('utm_campaign', 'like', '%'.$query.'%');
            })
            ->limit($limit)
            ->get()
            ->map(fn (MarketingCampaign $campaign) => [
                'type' => __('Campaign'),
                'label' => $this->label(),
                'title' => $campaign->name,
                'subtitle' => $campaign->statusLabel(),
                'url' => route('marketing.campaigns.show', $campaign),
                'workspace' => 'marketing',
            ])
            ->values();
    }
}
