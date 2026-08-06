<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\SalaryComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalaryComponent> */
class SalaryComponentFactory extends Factory
{
    protected $model = SalaryComponent::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->unique()->words(2, true),
            'code' => strtoupper(fake()->unique()->bothify('SC-###')),
            'component_type' => fake()->randomElement(['earning', 'deduction']),
            'is_taxable' => true,
            'is_recurring' => true,
            'formula_supported' => false,
            'is_active' => true,
            'description' => null,
        ];
    }

    public function earning(): static
    {
        return $this->state(fn () => ['component_type' => 'earning']);
    }

    public function deduction(): static
    {
        return $this->state(fn () => ['component_type' => 'deduction', 'is_taxable' => false]);
    }
}
