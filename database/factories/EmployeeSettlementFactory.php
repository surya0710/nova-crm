<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeExitProcess;
use App\Models\EmployeeSettlement;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeSettlement> */
class EmployeeSettlementFactory extends Factory
{
    protected $model = EmployeeSettlement::class;

    public function definition(): array
    {
        $pendingSalary = fake()->randomFloat(2, 10000, 50000);
        $leaveEncashment = fake()->randomFloat(2, 0, 15000);
        $loanRecovery = fake()->randomFloat(2, 0, 5000);
        $advanceRecovery = fake()->randomFloat(2, 0, 3000);
        $reimbursements = fake()->randomFloat(2, 0, 5000);
        $assetDeductions = fake()->randomFloat(2, 0, 2000);
        $statutoryDeductions = fake()->randomFloat(2, 0, 3000);
        $netSettlement = $pendingSalary + $leaveEncashment + $reimbursements
            - $loanRecovery - $advanceRecovery - $assetDeductions - $statutoryDeductions;

        return [
            'organization_id' => Organization::factory(),
            'employee_id' => Employee::factory(),
            'employee_exit_process_id' => EmployeeExitProcess::factory(),
            'settlement_number' => 'SET-'.fake()->unique()->numerify('########'),
            'status' => 'draft',
            'pending_salary' => $pendingSalary,
            'leave_encashment' => $leaveEncashment,
            'loan_recovery' => $loanRecovery,
            'advance_recovery' => $advanceRecovery,
            'reimbursements' => $reimbursements,
            'asset_deductions' => $assetDeductions,
            'statutory_deductions' => $statutoryDeductions,
            'net_settlement' => round($netSettlement, 2),
            'statement' => [],
            'notes' => fake()->optional()->sentence(),
            'completed_by' => null,
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'completed_by' => User::factory(),
            'completed_at' => now(),
        ]);
    }
}
