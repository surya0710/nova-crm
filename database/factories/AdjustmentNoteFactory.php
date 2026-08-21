<?php

namespace Database\Factories;

use App\Models\AdjustmentNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdjustmentNote>
 */
class AdjustmentNoteFactory extends Factory
{
    protected $model = AdjustmentNote::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 500);
        $tax = round($subtotal * 0.1, 2);

        return [
            'organization_id' => Organization::factory(),
            'number' => 'CN-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'type' => 'credit',
            'customer_id' => Customer::factory(),
            'invoice_id' => null,
            'opportunity_id' => null,
            'title' => fake()->optional()->sentence(3),
            'status' => 'draft',
            'reason' => 'price_adjustment',
            'reason_detail' => null,
            'issue_date' => now()->toDateString(),
            'currency' => 'USD',
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'taxable_amount' => $subtotal,
            'tax_total' => $tax,
            'total' => $subtotal + $tax,
            'applied_amount' => 0,
            'created_by' => User::factory(),
        ];
    }

    public function debit(): static
    {
        return $this->state(fn () => [
            'type' => 'debit',
            'number' => 'DN-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        ]);
    }

    public function issued(): static
    {
        return $this->state(fn () => ['status' => 'issued']);
    }

    public function forInvoice(Invoice $invoice): static
    {
        return $this->state(fn () => [
            'organization_id' => $invoice->organization_id,
            'customer_id' => $invoice->customer_id,
            'invoice_id' => $invoice->id,
            'currency' => $invoice->currency,
        ]);
    }
}
