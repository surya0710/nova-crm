<?php

namespace App\Http\Requests\Concerns;

use App\Rules\GstinFormat;
use App\Rules\PanFormat;
use App\Support\Gstin;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesCustomerTaxProfile
{
    /**
     * @return array<string, mixed>
     */
    protected function customerTaxProfileRules(): array
    {
        $stateCodes = array_keys(config('tax.states', []));

        return [
            'gstin' => ['nullable', 'string', 'size:15', new GstinFormat],
            'pan' => ['nullable', 'string', 'size:10', new PanFormat],
            'gst_registration_type' => ['nullable', 'string', Rule::in(array_keys(config('tax.gst_registration_types') ?? []))],
            'tax_registration_status' => ['nullable', 'string', Rule::in(array_keys(config('tax.tax_registration_statuses') ?? []))],
            'billing_state_code' => ['nullable', 'string', Rule::in($stateCodes)],
            'place_of_supply' => ['nullable', 'string', Rule::in($stateCodes)],
            'tax_exemption_status' => ['nullable', 'string', Rule::in(array_keys(config('tax.tax_exemption_statuses') ?? []))],
            'tax_exemption_reason' => ['nullable', 'required_if:tax_exemption_status,exempt', 'string', 'max:255'],
            'default_tax_preference' => ['nullable', 'string', Rule::in(array_keys(config('tax.tax_preferences') ?? []))],
            'shipping_same_as_billing' => ['nullable', 'boolean'],
            'shipping_address_line_1' => ['nullable', 'string', 'max:255'],
            'shipping_address_line_2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['nullable', 'string', 'max:255'],
            'shipping_state' => ['nullable', 'string', 'max:255'],
            'shipping_postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_country' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareCustomerTaxProfile(): void
    {
        $gstin = Gstin::normalize($this->input('gstin'));
        $pan = strtoupper(preg_replace('/\s+/', '', (string) $this->input('pan')) ?? '');
        $pan = $pan === '' ? null : $pan;

        if ($gstin && ! $pan) {
            $pan = Gstin::panFromGstin($gstin);
        }

        $billingState = $this->input('billing_state_code') ?: Gstin::stateCode($gstin);
        $placeOfSupply = $this->input('place_of_supply') ?: $billingState;

        $merge = [
            'gstin' => $gstin,
            'pan' => $pan,
        ];

        if ($billingState) {
            $merge['billing_state_code'] = $billingState;
        }

        if ($placeOfSupply) {
            $merge['place_of_supply'] = $placeOfSupply;
        }

        if ($this->has('tax_exemption_status') || $this->filled('tax_exemption_reason')) {
            $merge['tax_exemption_status'] = $this->input('tax_exemption_status') ?: 'not_exempt';
        }

        if ($this->has('shipping_same_as_billing')) {
            $merge['shipping_same_as_billing'] = $this->boolean('shipping_same_as_billing');
        }

        $this->merge($merge);

        $sameAsBilling = $this->has('shipping_same_as_billing')
            ? $this->boolean('shipping_same_as_billing')
            : true;

        if ($sameAsBilling && $this->has('shipping_same_as_billing')) {
            $this->merge([
                'shipping_address_line_1' => $this->input('address_line_1'),
                'shipping_address_line_2' => $this->input('address_line_2'),
                'shipping_city' => $this->input('city'),
                'shipping_state' => $this->input('state'),
                'shipping_postal_code' => $this->input('postal_code'),
                'shipping_country' => $this->input('country'),
            ]);
        }
    }

    protected function validateGstinPanConsistency(Validator $validator): void
    {
        $gstin = Gstin::normalize($this->input('gstin'));
        $pan = strtoupper((string) $this->input('pan'));

        if ($gstin && $pan !== '' && Gstin::panFromGstin($gstin) !== $pan) {
            $validator->errors()->add('pan', __('PAN must match the PAN embedded in the GSTIN.'));
        }
    }
}
