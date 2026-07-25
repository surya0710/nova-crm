<?php

namespace Database\Factories;

use App\Models\CompetencyCategory;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CompetencyCategory> */
class CompetencyCategoryFactory extends Factory
{
    protected $model = CompetencyCategory::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->unique()->words(2, true),
            'code' => strtoupper(fake()->unique()->bothify('CAT-###')),
            'description' => null,
            'is_active' => true,
        ];
    }
}
