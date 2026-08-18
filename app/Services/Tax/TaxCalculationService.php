<?php

namespace App\Services\Tax;

use App\Support\Money;
use Illuminate\Validation\ValidationException;

class TaxCalculationService
{
    public function __construct(protected TaxDeterminationService $determination) {}

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function calculateDocument(array $items, array $context = []): array
    {
        $this->validateItems($items);

        $subtotal = 0.0;
        $discountAmount = 0.0;
        $taxableAmount = 0.0;
        $cgst = 0.0;
        $sgst = 0.0;
        $igst = 0.0;
        $utgst = 0.0;
        $cess = 0.0;
        $otherTax = 0.0;
        $calculatedItems = [];

        foreach ($items as $index => $item) {
            $line = $this->calculateLine($item, $context);
            $line['sort_order'] = $index;
            $calculatedItems[] = $line;

            $subtotal += $line['line_subtotal'];
            $discountAmount += $line['discount_amount'];
            $taxableAmount += $line['taxable_amount'];
            $cgst += $line['cgst_amount'];
            $sgst += $line['sgst_amount'];
            $igst += $line['igst_amount'];
            $utgst += $line['utgst_amount'];
            $cess += $line['cess_amount'];
            $otherTax += $line['other_tax_amount'];
        }

        $taxTotal = Money::round($cgst + $sgst + $igst + $utgst + $cess + $otherTax);
        $shipping = Money::round((float) ($context['shipping_amount'] ?? 0));
        $pricingMode = $context['pricing_mode'] ?? 'exclusive';

        $netAfterDiscount = Money::round($subtotal - $discountAmount);
        $total = $pricingMode === 'inclusive'
            ? Money::round($netAfterDiscount + $shipping)
            : Money::round($netAfterDiscount + $taxTotal + $shipping);

        if ($total < 0) {
            throw ValidationException::withMessages([
                'items' => [__('Document total cannot be negative.')],
            ]);
        }

        return [
            'subtotal' => Money::round($subtotal),
            'discount_amount' => Money::round($discountAmount),
            'taxable_amount' => Money::round($taxableAmount),
            'cgst_amount' => Money::round($cgst),
            'sgst_amount' => Money::round($sgst),
            'igst_amount' => Money::round($igst),
            'utgst_amount' => Money::round($utgst),
            'cess_amount' => Money::round($cess),
            'other_tax_amount' => Money::round($otherTax),
            'tax_total' => $taxTotal,
            'shipping_amount' => $shipping,
            'total' => $total,
            'pricing_mode' => $pricingMode,
            'tax_treatment' => $context['tax_treatment'] ?? 'standard',
            'place_of_supply' => $context['place_of_supply'] ?? null,
            'items' => $calculatedItems,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function calculateLine(array $item, array $context = []): array
    {
        $quantity = (float) ($item['quantity'] ?? 0);
        $unitPrice = (float) ($item['unit_price'] ?? 0);
        $taxRate = (float) ($item['tax_rate'] ?? 0);
        $discountPercent = (float) ($item['discount_percent'] ?? 0);
        $cessRate = (float) ($item['cess_rate'] ?? 0);
        $pricingMode = ($item['tax_inclusive'] ?? false) || ($context['pricing_mode'] ?? 'exclusive') === 'inclusive'
            ? 'inclusive'
            : 'exclusive';
        $taxTreatment = $context['tax_treatment'] ?? 'standard';

        $lineSubtotal = Money::round($quantity * $unitPrice);
        $discountAmount = Money::round($lineSubtotal * ($discountPercent / 100));
        $net = Money::round($lineSubtotal - $discountAmount);

        if (in_array($taxTreatment, ['exempt', 'zero_rated'], true)) {
            $taxRate = 0;
            $cessRate = 0;
        }

        if ($pricingMode === 'inclusive' && ($taxRate + $cessRate) > 0) {
            $taxable = Money::round($net / (1 + (($taxRate + $cessRate) / 100)));
            $gstAmount = Money::round($taxable * ($taxRate / 100));
            $cessAmount = Money::round($taxable * ($cessRate / 100));
            $lineTotal = $net;
        } else {
            $taxable = $net;
            $gstAmount = Money::round($taxable * ($taxRate / 100));
            $cessAmount = Money::round($taxable * ($cessRate / 100));
            $lineTotal = Money::round($taxable + $gstAmount + $cessAmount);
        }

        $split = $this->determination->splitRates($taxRate, $context);
        $components = $this->componentAmounts($gstAmount, $split);

        return [
            'product_id' => $item['product_id'] ?? null,
            'sku' => $item['sku'] ?? null,
            'unit' => $item['unit'] ?? null,
            'hsn_sac' => $item['hsn_sac'] ?? null,
            'description' => $item['description'] ?? '',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
            'discount_percent' => $discountPercent,
            'line_subtotal' => $lineSubtotal,
            'discount_amount' => $discountAmount,
            'taxable_amount' => $taxable,
            'tax_amount' => Money::round($gstAmount + $cessAmount),
            'cgst_rate' => $split['cgst'],
            'cgst_amount' => $components['cgst'],
            'sgst_rate' => $split['sgst'],
            'sgst_amount' => $components['sgst'],
            'igst_rate' => $split['igst'],
            'igst_amount' => $components['igst'],
            'utgst_rate' => $split['utgst'],
            'utgst_amount' => $components['utgst'],
            'cess_rate' => $cessRate,
            'cess_amount' => $cessAmount,
            'other_tax_amount' => $components['other'],
            'tax_inclusive' => $pricingMode === 'inclusive',
            'line_total' => $lineTotal,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function validateItems(array $items): void
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => [__('At least one line item is required.')],
            ]);
        }

        $errors = [];

        foreach ($items as $index => $item) {
            $lineNumber = $index + 1;
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $taxRate = (float) ($item['tax_rate'] ?? 0);
            $discountPercent = (float) ($item['discount_percent'] ?? 0);
            $cessRate = (float) ($item['cess_rate'] ?? 0);
            $description = trim((string) ($item['description'] ?? ''));

            if ($description === '') {
                $errors["items.{$index}.description"] = __('Line :line requires a description.', ['line' => $lineNumber]);
            }

            if ($quantity <= 0) {
                $errors["items.{$index}.quantity"] = __('Line :line quantity must be greater than zero.', ['line' => $lineNumber]);
            }

            if ($unitPrice < 0) {
                $errors["items.{$index}.unit_price"] = __('Line :line unit price cannot be negative.', ['line' => $lineNumber]);
            }

            if ($discountPercent < 0 || $discountPercent > 100) {
                $errors["items.{$index}.discount_percent"] = __('Line :line discount must be between 0 and 100 percent.', ['line' => $lineNumber]);
            }

            if ($taxRate < 0 || $taxRate > 100) {
                $errors["items.{$index}.tax_rate"] = __('Line :line tax rate must be between 0 and 100 percent.', ['line' => $lineNumber]);
            }

            if ($cessRate < 0 || $cessRate > 100) {
                $errors["items.{$index}.cess_rate"] = __('Line :line cess rate must be between 0 and 100 percent.', ['line' => $lineNumber]);
            }

            $lineSubtotal = Money::round($quantity * $unitPrice);
            $discountAmount = Money::round($lineSubtotal * ($discountPercent / 100));

            if ($discountAmount > $lineSubtotal) {
                $errors["items.{$index}.discount_percent"] = __('Line :line discount cannot exceed the line value.', ['line' => $lineNumber]);
            }

            $line = $this->calculateLine($item);

            if ($line['line_total'] < 0) {
                $errors["items.{$index}.line_total"] = __('Line :line total cannot be negative.', ['line' => $lineNumber]);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array{cgst: float, sgst: float, igst: float, utgst: float, other: float}  $split
     * @return array{cgst: float, sgst: float, igst: float, utgst: float, other: float}
     */
    protected function componentAmounts(float $gstAmount, array $split): array
    {
        $rateTotal = $split['cgst'] + $split['sgst'] + $split['igst'] + $split['utgst'] + $split['other'];

        if ($gstAmount <= 0 || $rateTotal <= 0) {
            return ['cgst' => 0.0, 'sgst' => 0.0, 'igst' => 0.0, 'utgst' => 0.0, 'other' => 0.0];
        }

        $amounts = [
            'cgst' => $split['cgst'] > 0 ? Money::round($gstAmount * ($split['cgst'] / $rateTotal)) : 0.0,
            'sgst' => $split['sgst'] > 0 ? Money::round($gstAmount * ($split['sgst'] / $rateTotal)) : 0.0,
            'igst' => $split['igst'] > 0 ? Money::round($gstAmount * ($split['igst'] / $rateTotal)) : 0.0,
            'utgst' => $split['utgst'] > 0 ? Money::round($gstAmount * ($split['utgst'] / $rateTotal)) : 0.0,
            'other' => $split['other'] > 0 ? Money::round($gstAmount * ($split['other'] / $rateTotal)) : 0.0,
        ];

        $assigned = $amounts['cgst'] + $amounts['sgst'] + $amounts['igst'] + $amounts['utgst'] + $amounts['other'];
        $remainder = Money::round($gstAmount - $assigned);

        if ($remainder !== 0.0) {
            foreach (['igst', 'other', 'sgst', 'utgst', 'cgst'] as $component) {
                if ($amounts[$component] > 0 || $split[$component] > 0) {
                    $amounts[$component] = Money::round($amounts[$component] + $remainder);
                    break;
                }
            }
        }

        return $amounts;
    }
}
