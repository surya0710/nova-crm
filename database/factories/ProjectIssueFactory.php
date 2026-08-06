<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectIssue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectIssue> */
class ProjectIssueFactory extends Factory
{
    protected $model = ProjectIssue::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'project_id' => Project::factory(),
            'portfolio_id' => null,
            'program_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'severity' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'owner_id' => User::factory(),
            'status' => 'open',
            'resolution' => null,
            'root_cause' => null,
            'resolved_at' => null,
            'due_date' => fake()->optional()->dateTimeBetween('now', '+2 months'),
            'metadata' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ProjectIssue $issue) {
            $this->alignOrganization($issue);
        })->afterCreating(function (ProjectIssue $issue) {
            $this->alignOrganization($issue, true);
        });
    }

    protected function alignOrganization(ProjectIssue $issue, bool $persist = false): void
    {
        if (! $issue->project_id) {
            return;
        }

        $project = Project::query()->find($issue->project_id);

        if ($project && $issue->organization_id !== $project->organization_id) {
            $issue->organization_id = $project->organization_id;

            if ($persist) {
                $issue->saveQuietly();
            }
        }
    }
}
