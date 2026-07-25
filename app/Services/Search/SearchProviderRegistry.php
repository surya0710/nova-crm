<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;

class SearchProviderRegistry
{
    /** @var array<string, SearchProviderInterface> */
    protected array $providers = [];

    public function register(SearchProviderInterface $provider): void
    {
        $this->providers[$provider->key()] = $provider;
    }

    /**
     * @return array<string, SearchProviderInterface>
     */
    public function all(): array
    {
        return $this->providers;
    }

    public function get(string $key): ?SearchProviderInterface
    {
        return $this->providers[$key] ?? null;
    }

    /**
     * @return Collection<int, array{key: string, label: string}>
     */
    public function scopes(): Collection
    {
        return collect($this->providers)->map(fn (SearchProviderInterface $p) => [
            'key' => $p->key(),
            'label' => $p->label(),
        ])->values();
    }

    /**
     * @return Collection<int, array>
     */
    public function search(User $user, Organization $organization, string $query, ?string $scope = null, int $limit = 20): Collection
    {
        $scope = $scope ?: 'all';
        $provider = $this->get($scope) ?? $this->get('all');

        if (! $provider) {
            return collect();
        }

        return $provider->search($user, $organization, $query, $limit);
    }
}
