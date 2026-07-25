<?php

namespace Database\Factories;

use App\Models\Holiday;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Holiday> */
class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->randomElement(['New Year', 'Independence Day', 'Republic Day', 'Diwali', 'Christmas']),
            'holiday_date' => fake()->unique()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'is_optional' => false,
        ];
    }
}
