<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\ExpenseReimbursement;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExpenseReimbursement> */
class ExpenseReimbursementFactory extends Factory
{
    protected $model = ExpenseReimbursement::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'employee_id' => Employee::factory(),
            'claim_number' => 'CLM-'.fake()->unique()->numerify('########'),
            'category' => fake()->randomElement(['travel', 'meals', 'supplies', 'general']),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'is_taxable' => false,
            'status' => 'pending',
            'description' => fake()->sentence(),
            'payroll_run_id' => null,
            'included_at' => null,
            'requested_by' => User::factory(),
            'requested_at' => now(),
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }
}
