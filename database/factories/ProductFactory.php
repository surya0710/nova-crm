<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(3, true),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'description' => fake()->optional()->sentence(),
            'type' => fake()->randomElement(array_keys(config('products.types'))),
            'unit_price' => fake()->randomFloat(2, 10, 5000),
            'currency' => fake()->randomElement(array_keys(config('products.currencies'))),
            'unit' => fake()->randomElement(array_keys(config('products.units'))),
            'tax_rate' => fake()->randomElement([0, 5, 12, 18]),
            'default_discount_percent' => 0,
            'cost_price' => fake()->optional()->randomFloat(2, 5, 2500),
            'hsn_sac' => fake()->optional()->numerify('####'),
            'tax_inclusive' => false,
            'cess_rate' => 0,
            'category' => fake()->optional()->randomElement(['Software', 'Hardware', 'Consulting', 'Support']),
            'status' => fake()->randomElement(array_keys(config('products.statuses'))),
            'created_by' => User::factory(),
        ];
    }
}
