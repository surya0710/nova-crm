<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\PayrollLedgerEntry;
use App\Models\PayrollResult;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollLedgerEntry> */
class PayrollLedgerEntryFactory extends Factory
{
    protected $model = PayrollLedgerEntry::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'payroll_run_id' => PayrollRun::factory(),
            'payroll_result_id' => PayrollResult::factory(),
            'employee_id' => Employee::factory(),
            'account_code' => '5100',
            'account_name' => 'Salary Expense',
            'entry_type' => 'debit',
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'currency' => 'INR',
            'description' => fake()->optional()->sentence(),
            'is_reversal' => false,
            'reverses_entry_id' => null,
            'meta' => [],
            'generated_by' => User::factory(),
            'generated_at' => now(),
        ];
    }

    public function debit(): static
    {
        return $this->state(fn () => ['entry_type' => 'debit']);
    }

    public function credit(): static
    {
        return $this->state(fn () => ['entry_type' => 'credit']);
    }
}
