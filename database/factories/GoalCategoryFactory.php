<?php

namespace Database\Factories;

use App\Models\GoalCategory;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GoalCategory> */
class GoalCategoryFactory extends Factory
{
    protected $model = GoalCategory::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->unique()->words(2, true),
            'code' => strtoupper(fake()->unique()->bothify('GC-###')),
            'description' => null,
            'is_active' => true,
        ];
    }
}
