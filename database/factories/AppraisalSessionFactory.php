<?php

namespace Database\Factories;

use App\Models\AppraisalSession;
use App\Models\Organization;
use App\Models\PerformanceCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AppraisalSession> */
class AppraisalSessionFactory extends Factory
{
    protected $model = AppraisalSession::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'performance_cycle_id' => PerformanceCycle::factory(),
            'name' => fake()->year().' Appraisal Session',
            'description' => null,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'draft',
            'rating_weights' => config('hrms.appraisal.default_rating_weights'),
            'talent_matrix_config' => config('hrms.appraisal.default_talent_matrix'),
            'created_by' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }
}
