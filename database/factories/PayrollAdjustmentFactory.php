<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollAdjustment>
 */
class PayrollAdjustmentFactory extends Factory
{
    protected $model = PayrollAdjustment::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'employee_id' => Employee::factory(),
            'payroll_period_id' => null,
            'payroll_run_id' => null,
            'adjustment_number' => 'ADJ-'.fake()->unique()->numerify('######'),
            'adjustment_type' => 'bonus',
            'direction' => 'earning',
            'amount' => 5000,
            'status' => 'draft',
            'title' => 'Performance bonus',
            'description' => null,
            'effective_date' => now()->toDateString(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function forPeriod(PayrollPeriod $period): static
    {
        return $this->state(fn () => [
            'organization_id' => $period->organization_id,
            'payroll_period_id' => $period->id,
        ]);
    }
}
