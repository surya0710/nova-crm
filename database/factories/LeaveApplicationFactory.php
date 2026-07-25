<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LeaveApplication> */
class LeaveApplicationFactory extends Factory
{
    protected $model = LeaveApplication::class;

    public function definition(): array
    {
        $start = now()->addDays(5)->startOfDay();

        return [
            'organization_id' => Organization::factory(),
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(2)->toDateString(),
            'is_half_day' => false,
            'half_day_period' => null,
            'days' => 3,
            'reason' => fake()->sentence(),
            'status' => 'draft',
        ];
    }
}
