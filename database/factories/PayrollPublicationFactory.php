<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PayrollPublication;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollPublication> */
class PayrollPublicationFactory extends Factory
{
    protected $model = PayrollPublication::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'payroll_run_id' => PayrollRun::factory(),
            'published_by' => User::factory(),
            'published_at' => now(),
            'payslip_count' => 0,
            'email_queued_count' => 0,
            'status' => 'published',
            'meta' => [],
        ];
    }
}
