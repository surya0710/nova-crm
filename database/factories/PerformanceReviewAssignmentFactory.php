<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\PerformanceCycle;
use App\Models\PerformanceReviewAssignment;
use App\Models\PerformanceReviewTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PerformanceReviewAssignment> */
class PerformanceReviewAssignmentFactory extends Factory
{
    protected $model = PerformanceReviewAssignment::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'performance_cycle_id' => PerformanceCycle::factory(),
            'employee_id' => Employee::factory(),
            'review_template_id' => PerformanceReviewTemplate::factory(),
            'primary_reviewer_id' => null,
            'due_date' => now()->addDays(30)->toDateString(),
            'review_type' => 'manager',
            'status' => 'planned',
            'assigned_by' => null,
            'assigned_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (PerformanceReviewAssignment $assignment) {
            $this->alignOrganization($assignment);
        })->afterCreating(function (PerformanceReviewAssignment $assignment) {
            $this->alignOrganization($assignment, true);
        });
    }

    protected function alignOrganization(PerformanceReviewAssignment $assignment, bool $persist = false): void
    {
        $cycle = $assignment->performance_cycle_id
            ? PerformanceCycle::query()->find($assignment->performance_cycle_id)
            : null;

        if ($cycle && $assignment->organization_id !== $cycle->organization_id) {
            $assignment->organization_id = $cycle->organization_id;
            if ($persist) {
                $assignment->saveQuietly();
            }
        }
    }

    public function assigned(): static
    {
        return $this->state(fn () => [
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);
    }
}
