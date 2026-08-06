<?php

namespace Database\Factories;

use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Opportunity>
 */
class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'title' => fake()->sentence(3),
            'customer_id' => null,
            'stage' => fake()->randomElement(array_keys(config('pipeline.stages'))),
            'amount' => fake()->randomFloat(2, 5000, 250000),
            'currency' => 'USD',
            'probability' => fake()->numberBetween(10, 90),
            'expected_close_date' => fake()->dateTimeBetween('now', '+3 months'),
            'created_by' => User::factory(),
        ];
    }
}
