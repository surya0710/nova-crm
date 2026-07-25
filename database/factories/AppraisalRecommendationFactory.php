<?php

namespace Database\Factories;

use App\Models\AppraisalRecommendation;
use App\Models\EmployeeAppraisal;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AppraisalRecommendation> */
class AppraisalRecommendationFactory extends Factory
{
    protected $model = AppraisalRecommendation::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'employee_appraisal_id' => EmployeeAppraisal::factory(),
            'recommendation_type' => 'promotion',
            'promotion_recommendation' => 'recommended',
            'critical_role_flag' => false,
        ];
    }
}
