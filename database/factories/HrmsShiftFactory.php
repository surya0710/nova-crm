<?php

namespace Database\Factories;

use App\Models\HrmsShift;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HrmsShift> */
class HrmsShiftFactory extends Factory
{
    protected $model = HrmsShift::class;

    public function definition(): array
    {
        $preset = config('hrms.shift_presets.general');

        return [
            'organization_id' => Organization::factory(),
            'name' => $preset['name'].' '.fake()->unique()->numerify('##'),
            'code' => strtoupper(fake()->unique()->bothify('SH-###')),
            'start_time' => $preset['start_time'],
            'end_time' => $preset['end_time'],
            'break_minutes' => $preset['break_minutes'],
            'grace_period_minutes' => $preset['grace_period_minutes'] ?? 15,
            'working_hours' => 8,
            'minimum_working_minutes' => $preset['minimum_working_minutes'] ?? 420,
            'overtime_threshold_minutes' => $preset['overtime_threshold_minutes'] ?? 480,
            'is_overnight' => $preset['is_overnight'],
            'is_active' => true,
        ];
    }
}
