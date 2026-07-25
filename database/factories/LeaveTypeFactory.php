<?php

namespace Database\Factories;

use App\Models\LeaveType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LeaveType> */
class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        $defaults = config('hrms.default_leave_types.annual');

        return [
            'organization_id' => Organization::factory(),
            'name' => $defaults['name'].' '.fake()->unique()->numerify('##'),
            'code' => strtoupper(fake()->unique()->bothify('LT-###')),
            'is_paid' => $defaults['is_paid'],
            'requires_approval' => $defaults['requires_approval'],
            'requires_hr_approval' => $defaults['requires_hr_approval'],
            'allow_half_day' => $defaults['allow_half_day'],
            'max_days_per_year' => $defaults['max_days_per_year'],
            'is_active' => true,
        ];
    }
}
