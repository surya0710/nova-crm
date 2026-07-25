<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PayrollJournal;
use App\Models\PayrollReversal;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollReversal> */
class PayrollReversalFactory extends Factory
{
    protected $model = PayrollReversal::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'payroll_run_id' => PayrollRun::factory(),
            'reversal_number' => 'REV-'.fake()->unique()->numerify('########'),
            'reason' => fake()->sentence(),
            'reversing_journal_id' => PayrollJournal::factory(),
            'meta' => [],
            'reversed_by' => User::factory(),
            'reversed_at' => now(),
        ];
    }
}
