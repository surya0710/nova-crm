<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PayrollApproval;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollApproval> */
class PayrollApprovalFactory extends Factory
{
    protected $model = PayrollApproval::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'payroll_run_id' => PayrollRun::factory(),
            'approval_type' => 'hr',
            'approved_by' => User::factory(),
            'approved_at' => now(),
            'notes' => null,
        ];
    }
}
