<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectWatcher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectWatcher> */
class ProjectWatcherFactory extends Factory
{
    protected $model = ProjectWatcher::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ProjectWatcher $watcher) {
            $this->alignOrganization($watcher);
        })->afterCreating(function (ProjectWatcher $watcher) {
            $this->alignOrganization($watcher, true);
        });
    }

    protected function alignOrganization(ProjectWatcher $watcher, bool $persist = false): void
    {
        $project = $watcher->project_id
            ? Project::query()->find($watcher->project_id)
            : null;

        if ($project && $watcher->organization_id !== $project->organization_id) {
            $watcher->organization_id = $project->organization_id;

            if ($persist) {
                $watcher->saveQuietly();
            }
        }
    }
}
