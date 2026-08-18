<?php

namespace Tests\Unit;

use App\Services\Tax\TaxCalculationService;
use App\Services\Tax\TaxDeterminationService;
use App\Support\Gstin;
use Tests\TestCase;

class TaxCalculationServiceTest extends TestCase
{
    protected TaxCalculationService $tax;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tax = new TaxCalculationService(new TaxDeterminationService);
    }

    public function test_exclusive_tax_matches_existing_quotation_math(): void
    {
        $result = $this->tax->calculateDocument([
            [
                'description' => 'Consulting hours',
                'quantity' => 10,
                'unit_price' => 100,
                'tax_rate' => 10,
                'discount_percent' => 0,
            ],
        ]);

        $this->assertSame(1000.0, $result['subtotal']);
        $this->assertSame(0.0, $result['discount_amount']);
        $this->assertSame(100.0, $result['tax_total']);
        $this->assertSame(1100.0, $result['total']);
        $this->assertSame(100.0, $result['other_tax_amount']);
        $this->assertSame(0.0, $result['igst_amount']);
    }

    public function test_intra_state_splits_cgst_and_sgst(): void
    {
        $result = $this->tax->calculateDocument([
            [
                'description' => 'Software licence',
                'quantity' => 1,
                'unit_price' => 1000,
                'tax_rate' => 18,
                'discount_percent' => 0,
            ],
        ], [
            'seller_state_code' => '27',
            'place_of_supply' => '27',
            'supply_type' => 'intra_state',
            'uses_gst_split' => true,
            'pricing_mode' => 'exclusive',
            'tax_treatment' => 'standard',
        ]);

        $this->assertSame(90.0, $result['cgst_amount']);
        $this->assertSame(90.0, $result['sgst_amount']);
        $this->assertSame(0.0, $result['igst_amount']);
        $this->assertSame(180.0, $result['tax_total']);
        $this->assertSame(1180.0, $result['total']);
    }

    public function test_inter_state_uses_igst(): void
    {
        $result = $this->tax->calculateDocument([
            [
                'description' => 'Software licence',
                'quantity' => 1,
                'unit_price' => 1000,
                'tax_rate' => 18,
            ],
        ], [
            'seller_state_code' => '27',
            'place_of_supply' => '29',
            'supply_type' => 'inter_state',
            'uses_gst_split' => true,
            'pricing_mode' => 'exclusive',
            'tax_treatment' => 'standard',
        ]);

        $this->assertSame(0.0, $result['cgst_amount']);
        $this->assertSame(180.0, $result['igst_amount']);
        $this->assertSame(1180.0, $result['total']);
    }

    public function test_union_territory_uses_utgst(): void
    {
        $result = $this->tax->calculateDocument([
            [
                'description' => 'Support',
                'quantity' => 1,
                'unit_price' => 1000,
                'tax_rate' => 18,
            ],
        ], [
            'seller_state_code' => '04',
            'place_of_supply' => '04',
            'supply_type' => 'intra_state',
            'uses_gst_split' => true,
            'pricing_mode' => 'exclusive',
            'tax_treatment' => 'standard',
        ]);

        $this->assertSame(90.0, $result['cgst_amount']);
        $this->assertSame(90.0, $result['utgst_amount']);
        $this->assertSame(0.0, $result['sgst_amount']);
    }

    public function test_exempt_and_zero_rated_have_no_tax(): void
    {
        $items = [[
            'description' => 'Exempt service',
            'quantity' => 2,
            'unit_price' => 100,
            'tax_rate' => 18,
        ]];

        $exempt = $this->tax->calculateDocument($items, ['tax_treatment' => 'exempt']);
        $zeroRated = $this->tax->calculateDocument($items, ['tax_treatment' => 'zero_rated']);

        $this->assertSame(0.0, $exempt['tax_total']);
        $this->assertSame(200.0, $exempt['total']);
        $this->assertSame(0.0, $zeroRated['tax_total']);
        $this->assertSame(200.0, $zeroRated['total']);
    }

    public function test_tax_inclusive_extracts_tax_from_listed_price(): void
    {
        $result = $this->tax->calculateDocument([
            [
                'description' => 'Inclusive item',
                'quantity' => 1,
                'unit_price' => 118,
                'tax_rate' => 18,
            ],
        ], [
            'pricing_mode' => 'inclusive',
            'tax_treatment' => 'standard',
        ]);

        $this->assertSame(118.0, $result['subtotal']);
        $this->assertSame(100.0, $result['taxable_amount']);
        $this->assertSame(18.0, $result['tax_total']);
        $this->assertSame(118.0, $result['total']);
    }

    public function test_gstin_checksum_round_trip(): void
    {
        $base = '27ABCDE1234F1Z';
        $gstin = $base.Gstin::checksumCharacter($base);

        $this->assertTrue(Gstin::isValid($gstin));
        $this->assertSame('27', Gstin::stateCode($gstin));
        $this->assertSame('ABCDE1234F', Gstin::panFromGstin($gstin));
    }
}
