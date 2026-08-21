<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrder>
 */
class SalesOrderFactory extends Factory
{
    protected $model = SalesOrder::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 500, 10000);
        $discount = round($subtotal * 0.05, 2);
        $tax = round(($subtotal - $discount) * 0.1, 2);

        return [
            'organization_id' => Organization::factory(),
            'number' => 'SO-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'customer_id' => Customer::factory(),
            'quotation_id' => null,
            'opportunity_id' => null,
            'title' => fake()->optional()->sentence(3),
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'expected_delivery_date' => now()->addDays(14)->toDateString(),
            'currency' => fake()->randomElement(array_keys(config('sales_orders.currencies'))),
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_total' => $tax,
            'total' => $subtotal - $discount + $tax,
            'notes' => fake()->optional()->paragraph(),
            'created_by' => User::factory(),
        ];
    }
}
