<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\User;
use App\Services\MarketingProviderService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class AdminIntegrationSearchProvider implements SearchProviderInterface
{
    public function __construct(protected MarketingProviderService $providers) {}

    public function key(): string
    {
        return 'integrations';
    }

    public function label(): string
    {
        return __('Integrations');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasAnyPermission(['integrations.view', 'integrations.manage'])) {
            return collect();
        }

        $query = trim(mb_strtolower($query));
        if ($query === '') {
            return collect();
        }

        $href = Route::has('integrations.index') ? route('integrations.index') : null;
        if (! $href) {
            return collect();
        }

        return collect($this->providers->integrationCardsForOrganization($organization))
            ->filter(function (array $card) use ($query) {
                $name = mb_strtolower((string) ($card['name'] ?? ''));
                $slug = mb_strtolower((string) ($card['slug'] ?? ''));
                $channel = mb_strtolower((string) ($card['channel'] ?? ''));

                return str_contains($name, $query)
                    || str_contains($slug, $query)
                    || str_contains($channel, $query);
            })
            ->take($limit)
            ->map(fn (array $card) => [
                'type' => __('Integration'),
                'label' => $this->label(),
                'title' => $card['name'] ?? $card['slug'],
                'subtitle' => $card['status_label'] ?? ($card['status'] ?? null),
                'url' => $href,
                'workspace' => 'administration',
            ])
            ->values();
    }
}
