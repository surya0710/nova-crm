<?php

namespace Database\Factories;

use App\Models\AppraisalDevelopmentPlan;
use App\Models\EmployeeAppraisal;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AppraisalDevelopmentPlan> */
class AppraisalDevelopmentPlanFactory extends Factory
{
    protected $model = AppraisalDevelopmentPlan::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'employee_appraisal_id' => EmployeeAppraisal::factory(),
            'strengths' => null,
            'improvement_areas' => null,
            'learning_objectives' => null,
            'required_training' => null,
            'career_aspirations' => null,
            'target_completion_date' => null,
        ];
    }
}
