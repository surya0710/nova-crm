<?php

namespace Database\Factories;

use App\Models\EvaluationTemplate;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EvaluationTemplate> */
class EvaluationTemplateFactory extends Factory
{
    protected $model = EvaluationTemplate::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(3, true).' Scorecard',
            'is_active' => true,
        ];
    }
}
