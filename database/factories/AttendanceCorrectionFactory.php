<?php

namespace Database\Factories;

use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttendanceCorrection> */
class AttendanceCorrectionFactory extends Factory
{
    protected $model = AttendanceCorrection::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'attendance_record_id' => AttendanceRecord::factory(),
            'employee_id' => Employee::factory(),
            'reason' => fake()->sentence(),
            'status' => 'pending',
        ];
    }
}
