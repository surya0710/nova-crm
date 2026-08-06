<?php

namespace Database\Factories;

use App\Models\AssignmentPool;
use App\Models\AssignmentRule;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentRule>
 */
class AssignmentRuleFactory extends Factory
{
    protected $model = AssignmentRule::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(3, true).' Rule',
            'entity_type' => 'lead',
            'priority' => 100,
            'is_active' => true,
            'is_default' => false,
            'strategy' => null,
            'assignment_pool_id' => null,
            'conditions' => [],
        ];
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn () => [
            'organization_id' => $organization->id,
        ]);
    }

    public function forPool(AssignmentPool $pool): static
    {
        return $this->state(fn () => [
            'organization_id' => $pool->organization_id,
            'assignment_pool_id' => $pool->id,
        ]);
    }

    public function entityType(string $entityType): static
    {
        return $this->state(fn () => ['entity_type' => $entityType]);
    }

    public function priority(int $priority): static
    {
        return $this->state(fn () => ['priority' => $priority]);
    }

    public function defaultRule(): static
    {
        return $this->state(fn () => [
            'is_default' => true,
            'conditions' => [],
            'priority' => 9999,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    public function conditions(array $conditions): static
    {
        return $this->state(fn () => ['conditions' => $conditions]);
    }
}
