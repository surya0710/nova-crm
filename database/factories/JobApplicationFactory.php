<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JobApplication> */
class JobApplicationFactory extends Factory
{
    protected $model = JobApplication::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'candidate_id' => Candidate::factory(),
            'job_opening_id' => JobOpening::factory()->published(),
            'stage' => 'applied',
            'status' => 'active',
            'applied_date' => now()->toDateString(),
        ];
    }
}
