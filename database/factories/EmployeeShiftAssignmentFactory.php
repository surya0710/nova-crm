<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\HrmsShift;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeShiftAssignment> */
class EmployeeShiftAssignmentFactory extends Factory
{
    protected $model = EmployeeShiftAssignment::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'employee_id' => Employee::factory(),
            'shift_id' => HrmsShift::factory(),
            'effective_from' => now()->subMonth()->toDateString(),
            'effective_to' => null,
        ];
    }
}
