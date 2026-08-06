<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\PayrollResult;
use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollResult> */
class PayrollResultFactory extends Factory
{
    protected $model = PayrollResult::class;

    public function definition(): array
    {
        $gross = fake()->randomFloat(2, 20000, 80000);

        return [
            'organization_id' => Organization::factory(),
            'payroll_run_id' => PayrollRun::factory(),
            'employee_id' => Employee::factory(),
            'gross_salary' => $gross,
            'total_earnings' => $gross,
            'total_deductions' => 0,
            'net_salary' => $gross,
            'working_days' => 26,
            'payable_days' => 26,
            'overtime_minutes' => 0,
            'overtime_amount' => 0,
            'snapshot' => ['engine_version' => '10.3.2'],
            'calculation_hash' => hash('sha256', fake()->uuid()),
            'version' => 1,
        ];
    }
}
