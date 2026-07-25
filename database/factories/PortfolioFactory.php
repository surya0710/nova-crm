<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Portfolio> */
class PortfolioFactory extends Factory
{
    protected $model = Portfolio::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(3, true),
            'code' => strtoupper(fake()->unique()->bothify('PF-####')),
            'description' => fake()->optional()->paragraph(),
            'owner_id' => User::factory(),
            'status' => 'active',
            'color' => fake()->hexColor(),
            'start_date' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'target_end_date' => fake()->optional()->dateTimeBetween('now', '+1 year'),
            'archived_at' => null,
            'metadata' => null,
            'settings' => null,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => 'archived',
            'archived_at' => now(),
        ]);
    }
}
