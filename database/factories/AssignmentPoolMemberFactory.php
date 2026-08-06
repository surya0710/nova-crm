<?php

namespace Database\Factories;

use App\Models\AssignmentPool;
use App\Models\AssignmentPoolMember;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentPoolMember>
 */
class AssignmentPoolMemberFactory extends Factory
{
    protected $model = AssignmentPoolMember::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'assignment_pool_id' => AssignmentPool::factory(),
            'user_id' => User::factory(),
            'weight' => 1,
            'is_active' => true,
        ];
    }

    public function forPool(AssignmentPool $pool): static
    {
        return $this->state(fn () => [
            'organization_id' => $pool->organization_id,
            'assignment_pool_id' => $pool->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
        ]);
    }

    public function weight(int $weight): static
    {
        return $this->state(fn () => ['weight' => $weight]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
