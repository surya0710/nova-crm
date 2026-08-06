<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeLoan> */
class EmployeeLoanFactory extends Factory
{
    protected $model = EmployeeLoan::class;

    public function definition(): array
    {
        $principal = fake()->randomFloat(2, 5000, 100000);
        $monthlyRecovery = round($principal / fake()->numberBetween(6, 24), 2);

        return [
            'organization_id' => Organization::factory(),
            'employee_id' => Employee::factory(),
            'loan_number' => 'LOAN-'.fake()->unique()->numerify('########'),
            'loan_type' => 'general',
            'principal_amount' => $principal,
            'outstanding_balance' => $principal,
            'monthly_recovery' => $monthlyRecovery,
            'interest_rate' => fake()->optional()->randomFloat(4, 0, 12),
            'disbursed_on' => now()->subDays(fake()->numberBetween(1, 90))->toDateString(),
            'status' => 'active',
            'notes' => fake()->optional()->sentence(),
            'closed_at' => null,
            'closed_by' => null,
            'closure_reason' => null,
            'created_by' => User::factory(),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => 'closed',
            'outstanding_balance' => 0,
            'closed_at' => now(),
            'closed_by' => User::factory(),
            'closure_reason' => fake()->sentence(),
        ]);
    }
}
