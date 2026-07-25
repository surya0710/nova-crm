<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeExitProcess;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeExitProcess> */
class EmployeeExitProcessFactory extends Factory
{
    protected $model = EmployeeExitProcess::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'employee_id' => Employee::factory(),
            'exit_type' => fake()->randomElement(array_keys(config('hrms.exit_types', []))),
            'last_working_day' => now()->addDays(30)->toDateString(),
            'reason' => fake()->sentence(),
            'status' => 'in_progress',
        ];
    }
}
