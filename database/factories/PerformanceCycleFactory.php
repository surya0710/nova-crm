<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PerformanceCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PerformanceCycle> */
class PerformanceCycleFactory extends Factory
{
    protected $model = PerformanceCycle::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->unique()->year().' Annual Review',
            'cycle_type' => 'annual',
            'status' => 'draft',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'description' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }
}
