<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\SalaryAdvance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalaryAdvance> */
class SalaryAdvanceFactory extends Factory
{
    protected $model = SalaryAdvance::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 1000, 25000);

        return [
            'organization_id' => Organization::factory(),
            'employee_id' => Employee::factory(),
            'advance_number' => 'ADV-'.fake()->unique()->numerify('########'),
            'amount' => $amount,
            'outstanding_balance' => $amount,
            'monthly_recovery' => round($amount / fake()->numberBetween(1, 6), 2),
            'status' => 'pending',
            'reason' => fake()->sentence(),
            'notes' => fake()->optional()->sentence(),
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

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => 'active',
            'approved_by' => User::factory(),
            'approved_at' => now()->subDays(7),
        ]);
    }
}
