<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Organization::generateUniqueSlug($name),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'timezone' => 'UTC',
            'currency' => 'USD',
            'is_active' => true,
            'status' => 'active',
            'plan' => 'starter',
            'storage_used_bytes' => 0,
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => 'suspended',
            'is_active' => false,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => 'archived',
            'is_active' => false,
            'archived_at' => now(),
        ]);
    }
}
