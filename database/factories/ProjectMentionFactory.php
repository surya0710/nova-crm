<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMention;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectMention> */
class ProjectMentionFactory extends Factory
{
    protected $model = ProjectMention::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'project_id' => Project::factory(),
            'task_id' => null,
            'mentioned_user_id' => User::factory(),
            'mentioned_by' => User::factory(),
            'source_type' => (new TaskComment)->getMorphClass(),
            'source_id' => TaskComment::factory(),
            'excerpt' => fake()->optional()->sentence(),
            'read_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ProjectMention $mention) {
            $this->alignOrganization($mention);
        })->afterCreating(function (ProjectMention $mention) {
            $this->alignOrganization($mention, true);
        });
    }

    protected function alignOrganization(ProjectMention $mention, bool $persist = false): void
    {
        $organizationId = null;

        if ($mention->project_id) {
            $project = Project::query()->find($mention->project_id);
            $organizationId = $project?->organization_id;
        } elseif ($mention->task_id) {
            $task = Task::query()->find($mention->task_id);
            $organizationId = $task?->organization_id;
        }

        if ($organizationId !== null && $mention->organization_id !== $organizationId) {
            $mention->organization_id = $organizationId;

            if ($persist) {
                $mention->saveQuietly();
            }
        }
    }

    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()]);
    }
}
