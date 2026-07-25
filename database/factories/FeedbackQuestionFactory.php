<?php

namespace Database\Factories;

use App\Models\FeedbackQuestion;
use App\Models\FeedbackTemplate;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeedbackQuestion> */
class FeedbackQuestionFactory extends Factory
{
    protected $model = FeedbackQuestion::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'feedback_template_id' => FeedbackTemplate::factory(),
            'question_type' => 'rating',
            'competency_id' => null,
            'question_text' => fake()->sentence().'?',
            'help_text' => null,
            'scale_min' => 1,
            'scale_max' => 5,
            'sort_order' => 0,
            'is_required' => true,
        ];
    }
}
