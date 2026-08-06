<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Support\Collection;

class LegacySearchProvider implements SearchProviderInterface
{
    public function __construct(protected SearchService $search) {}

    public function key(): string
    {
        return 'all';
    }

    public function label(): string
    {
        return __('All');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        return $this->search->search($user, $query, $limit);
    }
}
