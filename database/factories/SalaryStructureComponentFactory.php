<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Models\SalaryStructureComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalaryStructureComponent> */
class SalaryStructureComponentFactory extends Factory
{
    protected $model = SalaryStructureComponent::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'salary_structure_id' => SalaryStructure::factory(),
            'salary_component_id' => SalaryComponent::factory(),
            'calculation_type' => 'fixed',
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'percentage' => null,
            'based_on_component_id' => null,
            'formula' => null,
            'sort_order' => 0,
        ];
    }
}
