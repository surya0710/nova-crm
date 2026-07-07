<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->name(),
            'company' => fake()->company(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'source' => fake()->randomElement(array_keys(config('leads.sources'))),
            'industry' => fake()->randomElement(['Technology', 'Healthcare', 'Finance', 'Retail', 'Manufacturing']),
            'budget' => fake()->randomFloat(2, 1000, 100000),
            'priority' => fake()->randomElement(array_keys(config('leads.priorities'))),
            'status' => fake()->randomElement(array_keys(config('leads.statuses'))),
            'tags' => fake()->randomElements(['hot', 'enterprise', 'follow-up', 'vip'], fake()->numberBetween(0, 2)),
            'created_by' => User::factory(),
        ];
    }
}
