<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\SalesOrder;

class CommercialPartySnapshot
{
    /**
     * @return array<string, mixed>
     */
    public function fromCustomer(?Customer $customer): array
    {
        if (! $customer) {
            return [];
        }

        return [
            'name' => $customer->display_name,
            'gstin' => $customer->gstin,
            'pan' => $customer->pan,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'gst_registration_type' => $customer->gst_registration_type,
            'place_of_supply' => $customer->place_of_supply ?: $customer->billing_state_code,
            'address_line_1' => $customer->address_line_1,
            'address_line_2' => $customer->address_line_2,
            'city' => $customer->city,
            'state' => $customer->state,
            'postal_code' => $customer->postal_code,
            'country' => $customer->country,
            'state_code' => $customer->billing_state_code,
            'lines' => $customer->billing_address_lines,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function shippingFromCustomer(?Customer $customer): array
    {
        if (! $customer) {
            return [];
        }

        $same = (bool) $customer->shipping_same_as_billing;

        return [
            'same_as_billing' => $same,
            'address_line_1' => $same ? $customer->address_line_1 : $customer->shipping_address_line_1,
            'address_line_2' => $same ? $customer->address_line_2 : $customer->shipping_address_line_2,
            'city' => $same ? $customer->city : $customer->shipping_city,
            'state' => $same ? $customer->state : $customer->shipping_state,
            'postal_code' => $same ? $customer->postal_code : $customer->shipping_postal_code,
            'country' => $same ? $customer->country : $customer->shipping_country,
            'lines' => $customer->shipping_address_lines,
        ];
    }

    /**
     * @return array{billing_snapshot: array<string, mixed>, shipping_snapshot: array<string, mixed>}
     */
    public function forCustomer(?Customer $customer): array
    {
        return [
            'billing_snapshot' => $this->fromCustomer($customer),
            'shipping_snapshot' => $this->shippingFromCustomer($customer),
        ];
    }

    /**
     * @return array{billing_snapshot: array<string, mixed>, shipping_snapshot: array<string, mixed>}
     */
    public function fromDocument(Quotation|Invoice|SalesOrder $document): array
    {
        $billing = is_array($document->billing_snapshot) ? $document->billing_snapshot : [];
        $shipping = is_array($document->shipping_snapshot) ? $document->shipping_snapshot : [];

        if ($billing !== [] || $shipping !== []) {
            return [
                'billing_snapshot' => $billing,
                'shipping_snapshot' => $shipping,
            ];
        }

        $document->loadMissing('customer');

        return $this->forCustomer($document->customer);
    }
}
