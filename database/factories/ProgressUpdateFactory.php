<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\ProgressUpdate;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProgressUpdate> */
class ProgressUpdateFactory extends Factory
{
    protected $model = ProgressUpdate::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'project_id' => Project::factory(),
            'milestone_id' => null,
            'updated_by' => User::factory(),
            'progress_percentage' => fake()->numberBetween(0, 100),
            'summary' => fake()->sentence(),
            'blockers' => fake()->optional()->paragraph(),
            'next_steps' => fake()->optional()->paragraph(),
            'metadata' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ProgressUpdate $update) {
            $this->alignOrganization($update);
        })->afterCreating(function (ProgressUpdate $update) {
            $this->alignOrganization($update, true);
        });
    }

    protected function alignOrganization(ProgressUpdate $update, bool $persist = false): void
    {
        $project = $update->project_id
            ? Project::query()->find($update->project_id)
            : null;

        if ($project && $update->organization_id !== $project->organization_id) {
            $update->organization_id = $project->organization_id;

            if ($persist) {
                $update->saveQuietly();
            }
        }
    }

    public function forMilestone(ProjectMilestone $milestone): static
    {
        return $this->state(fn () => [
            'organization_id' => $milestone->organization_id,
            'project_id' => $milestone->project_id,
            'milestone_id' => $milestone->id,
        ]);
    }
}
