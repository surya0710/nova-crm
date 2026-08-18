<?php

namespace App\Services\Tax;

final class CommercialDocumentFields
{
    /**
     * @return list<string>
     */
    public static function documentKeys(): array
    {
        return [
            'subtotal',
            'discount_amount',
            'taxable_amount',
            'tax_total',
            'cgst_amount',
            'sgst_amount',
            'igst_amount',
            'utgst_amount',
            'cess_amount',
            'other_tax_amount',
            'shipping_amount',
            'total',
            'pricing_mode',
            'tax_treatment',
            'place_of_supply',
        ];
    }

    /**
     * @return list<string>
     */
    public static function itemKeys(): array
    {
        return [
            'product_id',
            'sku',
            'unit',
            'hsn_sac',
            'description',
            'quantity',
            'unit_price',
            'tax_rate',
            'discount_percent',
            'line_subtotal',
            'discount_amount',
            'taxable_amount',
            'tax_amount',
            'cgst_rate',
            'cgst_amount',
            'sgst_rate',
            'sgst_amount',
            'igst_rate',
            'igst_amount',
            'utgst_rate',
            'utgst_amount',
            'cess_rate',
            'cess_amount',
            'tax_inclusive',
            'line_total',
            'sort_order',
        ];
    }

    /**
     * @param  array<string, mixed>  $totals
     * @return array<string, mixed>
     */
    public static function documentFromTotals(array $totals): array
    {
        $payload = [];

        foreach (self::documentKeys() as $key) {
            if (array_key_exists($key, $totals)) {
                $payload[$key] = $totals[$key];
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public static function itemFromCalculated(array $item): array
    {
        $payload = [];

        foreach (self::itemKeys() as $key) {
            if (array_key_exists($key, $item)) {
                $payload[$key] = $item[$key];
            }
        }

        return $payload;
    }
}
