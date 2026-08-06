<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\User;
use App\Services\MarketingProviderService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class MarketingProviderSearchProvider implements SearchProviderInterface
{
    public function __construct(protected MarketingProviderService $providers) {}

    public function key(): string
    {
        return 'marketing_providers';
    }

    public function label(): string
    {
        return __('Marketing Providers');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasAnyPermission(['marketing.view', 'marketing.manage', 'integrations.view', 'integrations.manage'])) {
            return collect();
        }

        $href = Route::has('marketing.providers.index')
            ? route('marketing.providers.index')
            : (Route::has('integrations.index') ? route('integrations.index') : null);

        if (! $href) {
            return collect();
        }

        $query = trim(mb_strtolower($query));
        if ($query === '') {
            return collect();
        }

        return collect($this->providers->integrationCardsForOrganization($organization))
            ->filter(function (array $card) use ($query) {
                $hay = mb_strtolower(($card['name'] ?? '').' '.($card['slug'] ?? '').' '.($card['channel'] ?? ''));

                return str_contains($hay, $query);
            })
            ->take($limit)
            ->map(fn (array $card) => [
                'type' => __('Provider'),
                'label' => $this->label(),
                'title' => $card['name'] ?? $card['slug'],
                'subtitle' => $card['status_label'] ?? null,
                'url' => $href,
                'workspace' => 'marketing',
            ])
            ->values();
    }
}
