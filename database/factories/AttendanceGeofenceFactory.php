<?php

namespace Database\Factories;

use App\Models\AttendanceGeofence;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttendanceGeofence> */
class AttendanceGeofenceFactory extends Factory
{
    protected $model = AttendanceGeofence::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'branch_id' => null,
            'name' => fake()->company().' Campus',
            'latitude' => fake()->latitude(12.9, 13.1),
            'longitude' => fake()->longitude(77.5, 77.7),
            'radius_meters' => fake()->numberBetween(50, 500),
            'is_active' => true,
            'effective_from' => null,
            'effective_to' => null,
        ];
    }
}
