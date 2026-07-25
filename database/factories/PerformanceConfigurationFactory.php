<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PerformanceConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PerformanceConfiguration> */
class PerformanceConfigurationFactory extends Factory
{
    protected $model = PerformanceConfiguration::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'default_review_frequency' => 'annual',
            'rating_scale_id' => null,
            'goal_weighting' => 50,
            'competency_weighting' => 50,
            'review_visibility' => 'employee_and_manager',
            'calibration_enabled' => false,
        ];
    }
}
