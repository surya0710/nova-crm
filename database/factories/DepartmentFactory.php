<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Department> */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->randomElement(['Engineering', 'Sales', 'HR', 'Finance', 'Operations']).' '.fake()->unique()->numerify('##'),
            'code' => strtoupper(fake()->unique()->bothify('DEPT-###')),
            'is_active' => true,
        ];
    }
}
