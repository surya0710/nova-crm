<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Task;
use App\Models\TaskDependency;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaskDependency> */
class TaskDependencyFactory extends Factory
{
    protected $model = TaskDependency::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'predecessor_task_id' => Task::factory(),
            'successor_task_id' => Task::factory(),
            'dependency_type' => fake()->randomElement(array_keys(config('tasks.dependency_types', ['finish_to_start' => 'Finish to Start']))),
        ];
    }
}
