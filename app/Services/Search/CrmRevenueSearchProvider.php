<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Support\Collection;

class CrmRevenueSearchProvider implements SearchProviderInterface
{
    public function __construct(protected SearchService $search) {}

    public function key(): string
    {
        return 'revenue';
    }

    public function label(): string
    {
        return __('Revenue');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        $results = collect();

        if ($user->hasPermission('quotations.view')) {
            $results = $results->merge($this->search->searchQuotations($query));
        }
        if ($user->hasPermission('invoices.view')) {
            $results = $results->merge($this->search->searchInvoices($query));
        }
        if ($user->hasPermission('payments.view')) {
            $results = $results->merge($this->search->searchPayments($query));
        }

        return $results->take($limit)->values();
    }
}
