<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(array_keys(config('tasks.statuses'))),
            'priority' => fake()->randomElement(array_keys(config('tasks.priorities'))),
            'due_at' => fake()->optional()->dateTimeBetween('now', '+2 weeks'),
            'assigned_to' => null,
            'created_by' => User::factory(),
        ];
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
}
