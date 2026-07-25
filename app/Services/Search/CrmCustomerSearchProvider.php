<?php

namespace App\Services\Search;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use App\Services\MetadataSearchService;
use Illuminate\Support\Collection;

class CrmCustomerSearchProvider implements SearchProviderInterface
{
    public function __construct(protected MetadataSearchService $metadataSearch) {}

    public function key(): string
    {
        return 'customers';
    }

    public function label(): string
    {
        return function_exists('crm_term') ? crm_term('customers') : __('Customers');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasPermission('customers.view')) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        return Customer::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('company', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");

                $this->metadataSearch->applySearchConstraint($q, 'customer', $query);
            })
            ->limit($limit)
            ->get()
            ->map(fn (Customer $customer) => [
                'type' => function_exists('crm_term') ? crm_term('customer') : __('Customer'),
                'label' => $this->label(),
                'title' => $customer->display_name,
                'subtitle' => $customer->email,
                'url' => route('customers.show', $customer),
                'workspace' => 'crm',
            ]);
    }
}
