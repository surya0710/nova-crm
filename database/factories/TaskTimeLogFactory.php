<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Task;
use App\Models\TaskTimeLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaskTimeLog> */
class TaskTimeLogFactory extends Factory
{
    protected $model = TaskTimeLog::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 week', 'now');
        $minutes = fake()->numberBetween(15, 240);

        return [
            'organization_id' => Organization::factory(),
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'start_time' => $start,
            'end_time' => (clone $start)->modify("+{$minutes} minutes"),
            'duration_minutes' => $minutes,
            'description' => fake()->optional()->sentence(),
            'source' => fake()->randomElement(array_keys(config('tasks.time_log_sources', ['manual' => 'Manual']))),
        ];
    }

    public function running(): static
    {
        return $this->state(fn () => [
            'end_time' => null,
            'duration_minutes' => 0,
            'source' => 'timer',
        ]);
    }
}
