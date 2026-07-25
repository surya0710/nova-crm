<?php

namespace Database\Factories;

use App\Models\Designation;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Designation> */
class DesignationFactory extends Factory
{
    protected $model = Designation::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->jobTitle(),
            'code' => strtoupper(fake()->unique()->bothify('DES-###')),
            'level' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
