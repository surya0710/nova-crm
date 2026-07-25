<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Task;
use App\Models\TaskWatcher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaskWatcher> */
class TaskWatcherFactory extends Factory
{
    protected $model = TaskWatcher::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (TaskWatcher $watcher) {
            $this->alignOrganization($watcher);
        })->afterCreating(function (TaskWatcher $watcher) {
            $this->alignOrganization($watcher, true);
        });
    }

    protected function alignOrganization(TaskWatcher $watcher, bool $persist = false): void
    {
        $task = $watcher->task_id
            ? Task::query()->find($watcher->task_id)
            : null;

        if ($task && $watcher->organization_id !== $task->organization_id) {
            $watcher->organization_id = $task->organization_id;

            if ($persist) {
                $watcher->saveQuietly();
            }
        }
    }
}
