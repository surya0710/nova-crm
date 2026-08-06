<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\SalaryStructure;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalaryStructure> */
class SalaryStructureFactory extends Factory
{
    protected $model = SalaryStructure::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->unique()->words(3, true).' Structure',
            'description' => fake()->optional()->sentence(),
            'effective_date' => now()->toDateString(),
            'is_active' => true,
        ];
    }
}
