<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Product;
use App\Services\Tax\TaxDeterminationService;
use App\Services\TenantContext;

class CommercialFormData
{
    public function __construct(protected TaxDeterminationService $taxDetermination) {}

    /**
     * @return array<string, mixed>
     */
    public function for(TenantContext $tenant): array
    {
        $organization = $tenant->get();
        $customers = Customer::query()->orderBy('name')->get();
        $products = Product::query()->where('status', 'active')->orderBy('name')->get();

        return [
            'customers' => $customers,
            'opportunities' => Opportunity::query()->with('customer')->orderBy('title')->get(),
            'products' => $products,
            'productOptions' => $products->map->catalogPayload()->values(),
            'customerTaxProfiles' => $customers->mapWithKeys(fn (Customer $customer) => [
                $customer->id => [
                    'place_of_supply' => $customer->place_of_supply ?: $customer->billing_state_code,
                    'tax_preference' => $customer->default_tax_preference ?: 'exclusive',
                    'exempt' => $customer->isTaxExempt(),
                    'gstin' => $customer->gstin,
                ],
            ]),
            'taxConfig' => [
                'states' => collect(config('tax.states', []))->map(fn ($state, $code) => [
                    'code' => $code,
                    'name' => $state['name'],
                    'utgst' => $state['utgst'],
                ])->values(),
                'seller_state_code' => $this->taxDetermination->normalizeStateCode(
                    $organization?->gst_state_code ?? $organization?->state
                ),
            ],
        ];
    }
}
