<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeWfhAssignment;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeWfhAssignment> */
class EmployeeWfhAssignmentFactory extends Factory
{
    protected $model = EmployeeWfhAssignment::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'employee_id' => Employee::factory(),
            'policy_type' => 'permanent',
            'weekdays' => null,
            'effective_from' => now()->toDateString(),
            'effective_to' => null,
            'is_active' => true,
            'reason' => fake()->optional()->sentence(),
            'assigned_by' => null,
        ];
    }

    public function permanent(): static
    {
        return $this->state(fn (): array => [
            'policy_type' => 'permanent',
            'weekdays' => null,
        ]);
    }

    public function selectedDays(array $weekdays = [1, 5]): static
    {
        return $this->state(fn (): array => [
            'policy_type' => 'selected_days',
            'weekdays' => $weekdays,
        ]);
    }
}
