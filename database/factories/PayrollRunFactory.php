<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollRun> */
class PayrollRunFactory extends Factory
{
    protected $model = PayrollRun::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'payroll_period_id' => PayrollPeriod::factory(),
            'status' => 'draft',
            'started_at' => null,
            'completed_at' => null,
            'triggered_by' => User::factory(),
            'employee_count' => 0,
            'success_count' => 0,
            'error_count' => 0,
            'engine_version' => '10.3.2',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }

    public function calculated(): static
    {
        return $this->state(fn () => [
            'status' => 'calculated',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
    }
}
