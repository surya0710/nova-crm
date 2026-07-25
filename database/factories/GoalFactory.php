<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Goal;
use App\Models\Organization;
use App\Models\PerformanceCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Goal> */
class GoalFactory extends Factory
{
    protected $model = Goal::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'performance_cycle_id' => PerformanceCycle::factory(),
            'title' => fake()->sentence(3),
            'description' => null,
            'goal_type' => 'individual',
            'assignee_type' => 'employee',
            'employee_id' => Employee::factory(),
            'measurement_type' => 'percentage',
            'target_value' => 100,
            'current_value' => 0,
            'weight' => 100,
            'achievement_percentage' => 0,
            'status' => 'assigned',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Goal $goal) {
            if ($goal->performance_cycle_id && ! $goal->organization_id) {
                $cycle = PerformanceCycle::query()->find($goal->performance_cycle_id);
                if ($cycle) {
                    $goal->organization_id = $cycle->organization_id;
                }
            }
        })->afterCreating(function (Goal $goal) {
            $cycle = $goal->cycle;
            if ($cycle && $goal->organization_id !== $cycle->organization_id) {
                $goal->forceFill(['organization_id' => $cycle->organization_id])->saveQuietly();
            }
        });
    }
}
