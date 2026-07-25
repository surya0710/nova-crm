<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\FeedbackCampaign;
use App\Models\FeedbackParticipant;
use App\Models\FeedbackRequest;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeedbackRequest> */
class FeedbackRequestFactory extends Factory
{
    protected $model = FeedbackRequest::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'feedback_campaign_id' => FeedbackCampaign::factory(),
            'feedback_participant_id' => FeedbackParticipant::factory(),
            'performance_review_id' => null,
            'subject_employee_id' => Employee::factory(),
            'participant_employee_id' => Employee::factory(),
            'participant_type' => 'peer',
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'pending',
            'is_anonymous' => true,
            'started_at' => null,
            'submitted_at' => null,
        ];
    }
}
