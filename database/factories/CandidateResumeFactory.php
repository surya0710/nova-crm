<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\CandidateResume;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CandidateResume> */
class CandidateResumeFactory extends Factory
{
    protected $model = CandidateResume::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'candidate_id' => Candidate::factory(),
            'name' => 'Primary Resume',
            'disk' => 'local',
            'path' => 'candidate-resumes/test/resume.pdf',
            'original_name' => 'resume.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'is_default' => true,
            'uploaded_at' => now(),
        ];
    }
}
