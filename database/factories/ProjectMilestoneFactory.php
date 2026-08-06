<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMilestone;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectMilestone> */
class ProjectMilestoneFactory extends Factory
{
    protected $model = ProjectMilestone::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'project_id' => Project::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'sequence' => fake()->numberBetween(1, 20),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+3 months'),
            'completed_at' => null,
            'status' => fake()->randomElement(array_keys(config('projects.milestone_statuses'))),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ProjectMilestone $milestone) {
            $this->alignOrganization($milestone);
        })->afterCreating(function (ProjectMilestone $milestone) {
            $this->alignOrganization($milestone, true);
        });
    }

    protected function alignOrganization(ProjectMilestone $milestone, bool $persist = false): void
    {
        $project = $milestone->project_id
            ? Project::query()->find($milestone->project_id)
            : null;

        if ($project && $milestone->organization_id !== $project->organization_id) {
            $milestone->organization_id = $project->organization_id;

            if ($persist) {
                $milestone->saveQuietly();
            }
        }
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
