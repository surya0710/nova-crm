<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 500, 10000);
        $discount = round($subtotal * 0.05, 2);
        $tax = round(($subtotal - $discount) * 0.1, 2);
        $total = $subtotal - $discount + $tax;

        return [
            'organization_id' => Organization::factory(),
            'number' => 'INV-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'customer_id' => Customer::factory(),
            'quotation_id' => null,
            'opportunity_id' => null,
            'title' => fake()->optional()->sentence(3),
            'status' => fake()->randomElement(array_keys(config('invoices.statuses'))),
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => fake()->randomElement(array_keys(config('invoices.currencies'))),
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_total' => $tax,
            'total' => $total,
            'amount_paid' => 0,
            'notes' => fake()->optional()->paragraph(),
            'created_by' => User::factory(),
        ];
    }
}
