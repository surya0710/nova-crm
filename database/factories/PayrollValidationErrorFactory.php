<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PayrollRun;
use App\Models\PayrollValidationError;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollValidationError> */
class PayrollValidationErrorFactory extends Factory
{
    protected $model = PayrollValidationError::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'payroll_run_id' => PayrollRun::factory(),
            'employee_id' => null,
            'code' => 'salary_assignment_missing',
            'message' => 'No active salary assignment found.',
            'context' => [],
        ];
    }
}
