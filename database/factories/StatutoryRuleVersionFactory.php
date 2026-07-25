<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\StatutoryRuleSet;
use App\Models\StatutoryRuleVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StatutoryRuleVersion> */
class StatutoryRuleVersionFactory extends Factory
{
    protected $model = StatutoryRuleVersion::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'statutory_rule_set_id' => StatutoryRuleSet::factory(),
            'version' => '2026.1',
            'effective_from' => '2026-01-01',
            'effective_until' => null,
            'jurisdiction' => 'IN',
            'configuration' => config('hrms.statutory.default_india_configuration', []),
            'is_active' => true,
        ];
    }
}
