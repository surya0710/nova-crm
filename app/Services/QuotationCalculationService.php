<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class QuotationCalculationService
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{subtotal: float, discount_amount: float, tax_total: float, total: float, items: array<int, array<string, mixed>>}
     */
    public function calculateTotals(array $items): array
    {
        $this->validateItems($items);

        $subtotal = 0.0;
        $discountAmount = 0.0;
        $taxTotal = 0.0;
        $calculatedItems = [];

        foreach ($items as $index => $item) {
            $line = $this->calculateLine($item);
            $line['sort_order'] = $index;
            $calculatedItems[] = $line;

            $qty = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $lineSubtotal = round($qty * $unitPrice, 2);

            $subtotal += $lineSubtotal;
            $discountAmount += $line['discount_amount'];
            $taxTotal += $line['tax_amount'];
        }

        $totals = [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'tax_total' => round($taxTotal, 2),
            'total' => round($subtotal - $discountAmount + $taxTotal, 2),
            'items' => $calculatedItems,
        ];

        if ($totals['total'] < 0) {
            throw ValidationException::withMessages([
                'items' => [__('Quotation total cannot be negative.')],
            ]);
        }

        return $totals;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function calculateLine(array $item): array
    {
        $quantity = (float) ($item['quantity'] ?? 0);
        $unitPrice = (float) ($item['unit_price'] ?? 0);
        $taxRate = (float) ($item['tax_rate'] ?? 0);
        $discountPercent = (float) ($item['discount_percent'] ?? 0);

        $lineSubtotal = round($quantity * $unitPrice, 2);
        $discountAmount = round($lineSubtotal * ($discountPercent / 100), 2);
        $taxable = $lineSubtotal - $discountAmount;
        $taxAmount = round($taxable * ($taxRate / 100), 2);
        $lineTotal = round($taxable + $taxAmount, 2);

        return [
            'product_id' => $item['product_id'] ?? null,
            'description' => $item['description'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'line_total' => $lineTotal,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     *
     * @throws ValidationException
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

            $lineSubtotal = round($quantity * $unitPrice, 2);
            $discountAmount = round($lineSubtotal * ($discountPercent / 100), 2);

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
}
