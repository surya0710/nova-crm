<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\RecruitmentSavedReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RecruitmentSavedReport> */
class RecruitmentSavedReportFactory extends Factory
{
    protected $model = RecruitmentSavedReport::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'report_name' => fake()->words(3, true),
            'report_type' => 'recruitment_summary',
            'filters_json' => ['period' => 'month'],
            'is_shared' => false,
        ];
    }
};
