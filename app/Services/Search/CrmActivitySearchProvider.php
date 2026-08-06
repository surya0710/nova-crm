<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Support\Collection;

class CrmActivitySearchProvider implements SearchProviderInterface
{
    public function __construct(protected SearchService $search) {}

    public function key(): string
    {
        return 'activities';
    }

    public function label(): string
    {
        return __('Activities');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        return $this->search->searchCrmActivities($user, $query, $limit);
    }
}
