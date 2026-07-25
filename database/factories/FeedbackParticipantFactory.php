<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\FeedbackCampaign;
use App\Models\FeedbackParticipant;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeedbackParticipant> */
class FeedbackParticipantFactory extends Factory
{
    protected $model = FeedbackParticipant::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'feedback_campaign_id' => FeedbackCampaign::factory(),
            'performance_review_id' => null,
            'subject_employee_id' => Employee::factory(),
            'participant_employee_id' => Employee::factory(),
            'external_name' => null,
            'external_email' => null,
            'participant_type' => 'peer',
            'status' => 'active',
        ];
    }
}
