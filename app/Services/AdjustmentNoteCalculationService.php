<?php

namespace App\Services;

use App\Services\Tax\TaxCalculationService;
use Illuminate\Validation\ValidationException;

class AdjustmentNoteCalculationService
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
                    fn (string $message): string => str_replace('Document total', 'Note total', $message),
                    $errors['items'],
                );
            }

            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function validateItems(array $items): void
    {
        $this->tax->validateItems($items);
    }
}
