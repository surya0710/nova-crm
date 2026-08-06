<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectBaseline;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectBaseline> */
class ProjectBaselineFactory extends Factory
{
    protected $model = ProjectBaseline::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'project_id' => Project::factory(),
            'version' => 1,
            'name' => fake()->optional()->words(3, true),
            'scope_snapshot' => ['milestones' => [], 'tasks' => []],
            'schedule_snapshot' => ['start_date' => null, 'end_date' => null],
            'budget_snapshot' => ['planned' => 0, 'actual' => 0],
            'progress_snapshot' => ['completion_percentage' => 0],
            'created_by' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ProjectBaseline $baseline) {
            $this->alignOrganization($baseline);
        })->afterCreating(function (ProjectBaseline $baseline) {
            $this->alignOrganization($baseline, true);
        });
    }

    protected function alignOrganization(ProjectBaseline $baseline, bool $persist = false): void
    {
        $project = $baseline->project_id
            ? Project::query()->find($baseline->project_id)
            : null;

        if ($project && $baseline->organization_id !== $project->organization_id) {
            $baseline->organization_id = $project->organization_id;

            if ($persist) {
                $baseline->saveQuietly();
            }
        }
    }
}
