<?php

namespace App\Models\Concerns;

use App\Services\CommercialPartySnapshot;

trait HasCommercialPartySnapshots
{
    /**
     * @return array<string, mixed>
     */
    public function resolvedBillingSnapshot(): array
    {
        if (is_array($this->billing_snapshot) && $this->billing_snapshot !== []) {
            return $this->billing_snapshot;
        }

        $this->loadMissing('customer');

        return app(CommercialPartySnapshot::class)->fromCustomer($this->customer);
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvedShippingSnapshot(): array
    {
        if (is_array($this->shipping_snapshot) && $this->shipping_snapshot !== []) {
            return $this->shipping_snapshot;
        }

        $this->loadMissing('customer');

        return app(CommercialPartySnapshot::class)->shippingFromCustomer($this->customer);
    }

    /**
     * @return list<string>
     */
    public function billingAddressLines(): array
    {
        $snapshot = $this->resolvedBillingSnapshot();

        if (! empty($snapshot['lines']) && is_array($snapshot['lines'])) {
            return array_values(array_filter($snapshot['lines']));
        }

        return array_values(array_filter([
            $snapshot['address_line_1'] ?? null,
            $snapshot['address_line_2'] ?? null,
            collect([
                $snapshot['city'] ?? null,
                $snapshot['state'] ?? null,
                $snapshot['postal_code'] ?? null,
            ])->filter()->join(', ') ?: null,
            $snapshot['country'] ?? null,
        ]));
    }

    /**
     * @return list<string>
     */
    public function shippingAddressLines(): array
    {
        $snapshot = $this->resolvedShippingSnapshot();

        if (! empty($snapshot['same_as_billing'])) {
            return $this->billingAddressLines();
        }

        if (! empty($snapshot['lines']) && is_array($snapshot['lines'])) {
            return array_values(array_filter($snapshot['lines']));
        }

        return array_values(array_filter([
            $snapshot['address_line_1'] ?? null,
            $snapshot['address_line_2'] ?? null,
            collect([
                $snapshot['city'] ?? null,
                $snapshot['state'] ?? null,
                $snapshot['postal_code'] ?? null,
            ])->filter()->join(', ') ?: null,
            $snapshot['country'] ?? null,
        ]));
    }

    public function placeOfSupplyLabel(): ?string
    {
        $code = $this->place_of_supply ?: data_get($this->resolvedBillingSnapshot(), 'place_of_supply');

        if (! $code) {
            return null;
        }

        $name = data_get(config('tax.states.'.$code), 'name');

        return $name ? $code.' — '.$name : (string) $code;
    }
}
