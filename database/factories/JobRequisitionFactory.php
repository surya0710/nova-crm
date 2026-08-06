<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Designation;
use App\Models\JobRequisition;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JobRequisition> */
class JobRequisitionFactory extends Factory
{
    protected $model = JobRequisition::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'department_id' => Department::factory(),
            'designation_id' => Designation::factory(),
            'employment_type' => 'full_time',
            'number_of_positions' => 1,
            'business_justification' => fake()->sentence(12),
            'status' => 'draft',
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved']);
    }
}
