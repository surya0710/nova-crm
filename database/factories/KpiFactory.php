<?php

namespace Database\Factories;

use App\Models\Kpi;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Kpi> */
class KpiFactory extends Factory
{
    protected $model = Kpi::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->unique()->words(2, true),
            'code' => strtoupper(fake()->unique()->bothify('KPI-###')),
            'unit' => 'count',
            'measurement_type' => 'numeric',
            'default_target' => 100,
            'description' => null,
            'is_active' => true,
        ];
    }
}
