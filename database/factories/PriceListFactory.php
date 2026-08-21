<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PriceList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceList>
 */
class PriceListFactory extends Factory
{
    protected $model = PriceList::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(2, true).' List',
            'currency' => 'USD',
            'is_default' => false,
            'status' => 'active',
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addYear()->toDateString(),
            'created_by' => User::factory(),
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => [
            'name' => 'Standard',
            'is_default' => true,
        ]);
    }
}
