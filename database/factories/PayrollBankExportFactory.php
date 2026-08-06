<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PayrollBankExport;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollBankExport> */
class PayrollBankExportFactory extends Factory
{
    protected $model = PayrollBankExport::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'payroll_run_id' => PayrollRun::factory(),
            'export_number' => 'EXP-'.fake()->unique()->numerify('########'),
            'format' => fake()->randomElement(['csv', 'xlsx']),
            'file_disk' => 'local',
            'file_path' => 'payroll/exports/'.fake()->uuid().'.csv',
            'employee_count' => fake()->numberBetween(1, 50),
            'total_amount' => fake()->randomFloat(2, 50000, 500000),
            'status' => 'generated',
            'meta' => [],
            'exported_by' => User::factory(),
            'exported_at' => now(),
        ];
    }
}
