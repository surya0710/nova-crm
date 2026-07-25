<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PerformanceRatingScale;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PerformanceRatingScale> */
class PerformanceRatingScaleFactory extends Factory
{
    protected $model = PerformanceRatingScale::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->unique()->words(3, true).' Scale',
            'code' => strtoupper(fake()->unique()->bothify('RS-###')),
            'description' => null,
            'is_default' => false,
            'is_active' => true,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }
}
