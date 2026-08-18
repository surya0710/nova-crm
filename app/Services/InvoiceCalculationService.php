<?php

namespace App\Services;

use App\Services\Tax\TaxCalculationService;
use App\Support\Money;
use Illuminate\Validation\ValidationException;

class InvoiceCalculationService
{
    public function __construct(protected TaxCalculationService $tax) {}

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function calculateTotals(array $items, array $context = []): array
    {
        try {
            return $this->tax->calculateDocument($items, $context);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            if (isset($errors['items'])) {
                $errors['items'] = array_map(
                    fn (string $message): string => str_replace('Document total', 'Invoice total', $message),
                    $errors['items'],
                );
            }

            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function calculateLine(array $item, array $context = []): array
    {
        return $this->tax->calculateLine($item, $context);
    }

    public function balanceDue(float $total, float $amountPaid): float
    {
        return Money::balanceDue($total, $amountPaid);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function validateItems(array $items): void
    {
        $this->tax->validateItems($items);
    }
}
