<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeSalaryAssignment;
use App\Models\Organization;
use App\Models\SalaryStructure;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeSalaryAssignment> */
class EmployeeSalaryAssignmentFactory extends Factory
{
    protected $model = EmployeeSalaryAssignment::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'employee_id' => Employee::factory(),
            'salary_structure_id' => SalaryStructure::factory(),
            'effective_from' => now()->toDateString(),
            'effective_until' => null,
            'annual_ctc' => fake()->randomFloat(2, 300000, 2000000),
            'notes' => null,
            'assigned_by' => null,
        ];
    }
}
