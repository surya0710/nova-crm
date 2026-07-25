<?php

namespace Database\Factories;

use App\Models\FeedbackTemplate;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeedbackTemplate> */
class FeedbackTemplateFactory extends Factory
{
    protected $model = FeedbackTemplate::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(2, true).' Feedback Form',
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
