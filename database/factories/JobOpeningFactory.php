<?php

namespace Database\Factories;

use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JobOpening> */
class JobOpeningFactory extends Factory
{
    protected $model = JobOpening::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'job_requisition_id' => JobRequisition::factory()->approved(),
            'title' => fake()->jobTitle(),
            'department_id' => fn (array $attributes) => JobRequisition::query()->find($attributes['job_requisition_id'])?->department_id,
            'designation_id' => fn (array $attributes) => JobRequisition::query()->find($attributes['job_requisition_id'])?->designation_id,
            'employment_type' => 'full_time',
            'status' => 'draft',
        ];
    }

    public function published(): static
    {
        return $this->state([
            'status' => 'published',
            'publish_date' => now()->toDateString(),
        ]);
    }
}
