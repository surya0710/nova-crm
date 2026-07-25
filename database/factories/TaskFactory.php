<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'project_id' => null,
            'parent_task_id' => null,
            'milestone_id' => null,
            'status_id' => null,
            'priority_id' => null,
            'task_number' => null,
            'slug' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(array_keys(config('tasks.statuses'))),
            'priority' => fake()->randomElement(array_keys(config('tasks.priorities'))),
            'due_at' => fake()->optional()->dateTimeBetween('now', '+2 weeks'),
            'assigned_to' => null,
            'assigned_by' => null,
            'estimated_hours' => null,
            'actual_hours' => null,
            'start_date' => null,
            'due_date' => null,
            'completion_percentage' => 0,
            'metadata' => null,
            'settings' => null,
            'sort_order' => 0,
            'is_archived' => false,
            'created_by' => User::factory(),
        ];
    }

    public function forProject(?Project $project = null): static
    {
        return $this->state(function () use ($project) {
            $project ??= Project::factory()->create();
            $prefix = config('tasks.number_prefix', 'TASK');
            $padding = (int) config('tasks.number_padding', 4);
            $title = fake()->sentence(4);

            return [
                'organization_id' => $project->organization_id,
                'project_id' => $project->id,
                'status_id' => TaskStatus::factory()->state(['organization_id' => $project->organization_id]),
                'priority_id' => TaskPriority::factory()->state(['organization_id' => $project->organization_id]),
                'task_number' => $prefix.'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), $padding, '0', STR_PAD_LEFT),
                'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
                'title' => $title,
                'status' => 'pending',
                'priority' => 'medium',
            ];
        });
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
            'completed_at' => null,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
            'due_at' => now()->subDay(),
            'completed_at' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['is_archived' => true]);
    }
}
