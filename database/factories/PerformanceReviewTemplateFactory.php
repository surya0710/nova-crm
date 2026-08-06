<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PerformanceReviewTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PerformanceReviewTemplate> */
class PerformanceReviewTemplateFactory extends Factory
{
    protected $model = PerformanceReviewTemplate::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->unique()->words(3, true).' Template',
            'code' => strtoupper(fake()->unique()->bothify('TPL-###')),
            'description' => null,
            'instructions' => null,
            'is_active' => true,
        ];
    }
}
