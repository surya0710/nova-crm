<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Task;
use App\Models\TaskRecurrence;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaskRecurrence> */
class TaskRecurrenceFactory extends Factory
{
    protected $model = TaskRecurrence::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'task_id' => Task::factory(),
            'recurrence_type' => fake()->randomElement(['daily', 'weekly', 'monthly', 'yearly']),
            'interval' => 1,
            'days_of_week' => null,
            'end_type' => 'never',
            'end_date' => null,
            'occurrences' => null,
            'generated_count' => 0,
            'skip_holidays' => false,
            'copy_attachments' => false,
            'is_active' => true,
            'last_generated_at' => null,
            'next_run_at' => fake()->optional()->dateTimeBetween('now', '+1 month'),
            'settings' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (TaskRecurrence $recurrence) {
            $this->alignOrganization($recurrence);
        })->afterCreating(function (TaskRecurrence $recurrence) {
            $this->alignOrganization($recurrence, true);
        });
    }

    protected function alignOrganization(TaskRecurrence $recurrence, bool $persist = false): void
    {
        $task = $recurrence->task_id
            ? Task::query()->find($recurrence->task_id)
            : null;

        if ($task && $recurrence->organization_id !== $task->organization_id) {
            $recurrence->organization_id = $task->organization_id;

            if ($persist) {
                $recurrence->saveQuietly();
            }
        }
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
