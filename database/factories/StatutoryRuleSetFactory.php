<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\StatutoryRuleSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StatutoryRuleSet> */
class StatutoryRuleSetFactory extends Factory
{
    protected $model = StatutoryRuleSet::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'code' => 'india_2026',
            'name' => 'India 2026',
            'jurisdiction' => 'IN',
            'description' => 'Indian statutory payroll compliance pack',
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['is_active' => true]);
    }
}
