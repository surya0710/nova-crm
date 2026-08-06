<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\StatutoryComplianceError;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StatutoryComplianceError> */
class StatutoryComplianceErrorFactory extends Factory
{
    protected $model = StatutoryComplianceError::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'employee_id' => null,
            'payroll_run_id' => null,
            'payroll_result_id' => null,
            'statutory_rule_set_id' => null,
            'statutory_rule_version_id' => null,
            'code' => 'missing_rule_set',
            'message' => 'No active statutory rule set is configured.',
            'context' => [],
        ];
    }
}
