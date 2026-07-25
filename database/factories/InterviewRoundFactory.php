<?php

namespace Database\Factories;

use App\Models\EvaluationTemplate;
use App\Models\InterviewRound;
use App\Models\InterviewStage;
use App\Models\JobApplication;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InterviewRound> */
class InterviewRoundFactory extends Factory
{
    protected $model = InterviewRound::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'job_application_id' => JobApplication::factory(),
            'interview_stage_id' => InterviewStage::factory(),
            'round_number' => 1,
            'interview_type' => 'video',
            'scheduled_at' => now()->addDays(3),
            'duration_minutes' => 60,
            'location' => 'Conference Room A',
            'status' => 'scheduled',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => 'draft',
            'scheduled_at' => null,
        ]);
    }
}
