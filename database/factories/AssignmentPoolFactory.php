<?php

namespace Database\Factories;

use App\Models\AssignmentPool;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentPool>
 */
class AssignmentPoolFactory extends Factory
{
    protected $model = AssignmentPool::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(2, true).' Pool',
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'strategy' => 'round_robin',
            'rotation_position' => 0,
        ];
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn () => [
            'organization_id' => $organization->id,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function strategy(string $strategy): static
    {
        return $this->state(fn () => ['strategy' => $strategy]);
    }
}
