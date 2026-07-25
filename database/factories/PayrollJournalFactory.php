<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PayrollJournal;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollJournal> */
class PayrollJournalFactory extends Factory
{
    protected $model = PayrollJournal::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 10000, 100000);

        return [
            'organization_id' => Organization::factory(),
            'payroll_run_id' => PayrollRun::factory(),
            'journal_number' => 'JRN-'.fake()->unique()->numerify('########'),
            'journal_date' => now()->toDateString(),
            'description' => fake()->optional()->sentence(),
            'status' => 'posted',
            'total_debit' => $amount,
            'total_credit' => $amount,
            'is_reversal' => false,
            'reverses_journal_id' => null,
            'meta' => [],
            'created_by' => User::factory(),
        ];
    }

    public function reversed(): static
    {
        return $this->state(fn () => [
            'status' => 'reversed',
            'is_reversal' => true,
        ]);
    }
}
