<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectHealthSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectHealthSnapshot> */
class ProjectHealthSnapshotFactory extends Factory
{
    protected $model = ProjectHealthSnapshot::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'project_id' => Project::factory(),
            'health_status' => fake()->randomElement(array_keys(config('projects.health_statuses', ['on_track' => 'On Track']))),
            'completion_percentage' => fake()->numberBetween(0, 100),
            'schedule_variance' => fake()->optional()->randomFloat(2, 0, 30),
            'budget_variance' => fake()->optional()->randomFloat(2, -10000, 10000),
            'estimated_completion_date' => fake()->optional()->dateTimeBetween('now', '+6 months'),
            'calculated_at' => now(),
            'metadata' => [
                'metrics' => [
                    'task_completion_percentage' => fake()->numberBetween(0, 100),
                    'milestone_completion_percentage' => fake()->numberBetween(0, 100),
                    'manual_completion_percentage' => fake()->numberBetween(0, 100),
                ],
            ],
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ProjectHealthSnapshot $snapshot) {
            $this->alignOrganization($snapshot);
        })->afterCreating(function (ProjectHealthSnapshot $snapshot) {
            $this->alignOrganization($snapshot, true);
        });
    }

    protected function alignOrganization(ProjectHealthSnapshot $snapshot, bool $persist = false): void
    {
        $project = $snapshot->project_id
            ? Project::query()->find($snapshot->project_id)
            : null;

        if ($project && $snapshot->organization_id !== $project->organization_id) {
            $snapshot->organization_id = $project->organization_id;

            if ($persist) {
                $snapshot->saveQuietly();
            }
        }
    }
}
