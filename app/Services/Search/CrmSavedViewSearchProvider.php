<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Support\Collection;

class CrmSavedViewSearchProvider implements SearchProviderInterface
{
    public function __construct(protected SearchService $search) {}

    public function key(): string
    {
        return 'saved_views';
    }

    public function label(): string
    {
        return __('Saved Views');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        return $this->search->searchSavedViews($user, $query, $limit);
    }
}
