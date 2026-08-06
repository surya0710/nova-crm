<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Task;
use App\Models\TaskChecklist;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaskChecklist> */
class TaskChecklistFactory extends Factory
{
    protected $model = TaskChecklist::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'task_id' => Task::factory(),
            'title' => fake()->sentence(3),
            'sequence' => fake()->numberBetween(0, 20),
            'is_completed' => false,
            'completed_by' => null,
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }
}
