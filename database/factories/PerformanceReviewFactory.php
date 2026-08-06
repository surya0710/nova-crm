<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\PerformanceCycle;
use App\Models\PerformanceReview;
use App\Models\PerformanceReviewAssignment;
use App\Models\PerformanceReviewTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PerformanceReview> */
class PerformanceReviewFactory extends Factory
{
    protected $model = PerformanceReview::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'review_assignment_id' => PerformanceReviewAssignment::factory(),
            'performance_cycle_id' => PerformanceCycle::factory(),
            'employee_id' => Employee::factory(),
            'review_template_id' => PerformanceReviewTemplate::factory(),
            'reviewer_id' => null,
            'review_type' => 'manager',
            'status' => 'draft',
            'overall_comments' => null,
            'development_notes' => null,
            'strengths' => null,
            'improvement_areas' => null,
            'snapshot' => null,
            'snapshot_hash' => null,
            'started_at' => null,
            'submitted_at' => null,
            'reviewed_at' => null,
            'closed_at' => null,
        ];
    }
}
