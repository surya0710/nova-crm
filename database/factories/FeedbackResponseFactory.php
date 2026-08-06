<?php

namespace Database\Factories;

use App\Models\FeedbackQuestion;
use App\Models\FeedbackRequest;
use App\Models\FeedbackResponse;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeedbackResponse> */
class FeedbackResponseFactory extends Factory
{
    protected $model = FeedbackResponse::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'feedback_request_id' => FeedbackRequest::factory(),
            'feedback_question_id' => FeedbackQuestion::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'text_response' => null,
            'reviewer_employee_id' => null,
            'submitted_at' => now(),
        ];
    }
}
