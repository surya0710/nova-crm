<?php

namespace App\Services\Tax;

use App\Models\Customer;
use App\Models\Organization;
use App\Support\Gstin;

class TaxDeterminationService
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array{
     *     seller_state_code: ?string,
     *     place_of_supply: ?string,
     *     supply_type: string,
     *     pricing_mode: string,
     *     tax_treatment: string,
     *     uses_gst_split: bool
     * }
     */
    public function contextFor(Organization $organization, ?Customer $customer, array $overrides = []): array
    {
        $sellerState = $this->normalizeStateCode(
            $overrides['seller_state_code'] ?? $organization->gst_state_code ?? $organization->state
        );

        $placeOfSupply = $this->normalizeStateCode(
            $overrides['place_of_supply']
                ?? $customer?->place_of_supply
                ?? $customer?->billing_state_code
                ?? Gstin::stateCode($customer?->gstin)
                ?? $customer?->state
        );

        $preference = $overrides['default_tax_preference']
            ?? $customer?->default_tax_preference
            ?? 'exclusive';

        $taxTreatment = $overrides['tax_treatment'] ?? $this->treatmentFromPreference(
            $preference,
            $customer?->tax_exemption_status === 'exempt'
        );

        $pricingMode = $overrides['pricing_mode'] ?? match ($preference) {
            'inclusive' => 'inclusive',
            default => 'exclusive',
        };

        if (in_array($taxTreatment, ['exempt', 'zero_rated'], true)) {
            $pricingMode = 'exclusive';
        }

        $usesGstSplit = $sellerState !== null && $placeOfSupply !== null;
        $supplyType = 'other';

        if ($usesGstSplit) {
            $supplyType = $sellerState === $placeOfSupply ? 'intra_state' : 'inter_state';
        }

        return [
            'seller_state_code' => $sellerState,
            'place_of_supply' => $placeOfSupply,
            'supply_type' => $supplyType,
            'pricing_mode' => $pricingMode,
            'tax_treatment' => $taxTreatment,
            'uses_gst_split' => $usesGstSplit,
        ];
    }

    /**
     * @return array{cgst: float, sgst: float, igst: float, utgst: float, other: float}
     */
    public function splitRates(float $taxRate, array $context): array
    {
        $zero = ['cgst' => 0.0, 'sgst' => 0.0, 'igst' => 0.0, 'utgst' => 0.0, 'other' => 0.0];

        if ($taxRate <= 0 || in_array($context['tax_treatment'] ?? 'standard', ['exempt', 'zero_rated'], true)) {
            return $zero;
        }

        if (! ($context['uses_gst_split'] ?? false)) {
            $zero['other'] = $taxRate;

            return $zero;
        }

        if (($context['supply_type'] ?? 'other') === 'inter_state') {
            $zero['igst'] = $taxRate;

            return $zero;
        }

        $half = round($taxRate / 2, 2);
        $zero['cgst'] = $half;

        if ($this->usesUtgst($context['place_of_supply'] ?? null)) {
            $zero['utgst'] = $half;
        } else {
            $zero['sgst'] = $half;
        }

        return $zero;
    }

    public function usesUtgst(?string $stateCode): bool
    {
        $stateCode = $this->normalizeStateCode($stateCode);

        if ($stateCode === null) {
            return false;
        }

        return (bool) data_get(config('tax.states.'.$stateCode), 'utgst', false);
    }

    public function normalizeStateCode(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{1,2}$/', $value)) {
            $code = str_pad($value, 2, '0', STR_PAD_LEFT);

            return array_key_exists($code, config('tax.states', [])) ? $code : null;
        }

        foreach (config('tax.states', []) as $code => $state) {
            if (strcasecmp((string) $state['name'], $value) === 0) {
                return (string) $code;
            }
        }

        return null;
    }

    protected function treatmentFromPreference(string $preference, bool $customerExempt): string
    {
        if ($customerExempt || $preference === 'exempt') {
            return 'exempt';
        }

        if ($preference === 'zero_rated') {
            return 'zero_rated';
        }

        return 'standard';
    }
}
