<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PayrollConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollConfiguration> */
class PayrollConfigurationFactory extends Factory
{
    protected $model = PayrollConfiguration::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'payroll_frequency' => 'monthly',
            'currency' => 'INR',
            'working_days_per_month' => 26,
            'week_off_days' => ['saturday', 'sunday'],
            'overtime_handling' => 'pay',
            'rounding_policy' => 'nearest',
        ];
    }
}
