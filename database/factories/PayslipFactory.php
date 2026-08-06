<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\PayrollResult;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payslip> */
class PayslipFactory extends Factory
{
    protected $model = Payslip::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'payroll_run_id' => PayrollRun::factory(),
            'payroll_result_id' => PayrollResult::factory(),
            'payroll_publication_id' => null,
            'employee_id' => Employee::factory(),
            'payslip_number' => 'PS-'.fake()->unique()->numerify('########'),
            'gross_salary' => 10000,
            'total_earnings' => 10000,
            'total_deductions' => 500,
            'employer_contributions' => 1200,
            'net_salary' => 9500,
            'snapshot' => ['earnings' => [], 'deductions' => [], 'totals' => []],
            'calculation_hash' => hash('sha256', 'test'),
            'generated_at' => now(),
            'emailed_at' => null,
            'email_count' => 0,
        ];
    }
}
