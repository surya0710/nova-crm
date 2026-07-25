<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectRisk;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectRisk> */
class ProjectRiskFactory extends Factory
{
    protected $model = ProjectRisk::class;

    public function definition(): array
    {
        $probability = fake()->numberBetween(1, 5);
        $impact = fake()->numberBetween(1, 5);

        return [
            'organization_id' => Organization::factory(),
            'project_id' => Project::factory(),
            'portfolio_id' => null,
            'program_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'category' => fake()->optional()->randomElement(['schedule', 'budget', 'resource', 'technical', 'external']),
            'probability' => $probability,
            'impact' => $impact,
            'severity' => ProjectRisk::computeSeverity($probability, $impact),
            'mitigation_plan' => fake()->optional()->paragraph(),
            'contingency_plan' => fake()->optional()->paragraph(),
            'owner_id' => User::factory(),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+3 months'),
            'status' => 'open',
            'escalated_at' => null,
            'history' => null,
            'metadata' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ProjectRisk $risk) {
            $this->alignOrganization($risk);
        })->afterCreating(function (ProjectRisk $risk) {
            $this->alignOrganization($risk, true);
        });
    }

    protected function alignOrganization(ProjectRisk $risk, bool $persist = false): void
    {
        if (! $risk->project_id) {
            return;
        }

        $project = Project::query()->find($risk->project_id);

        if ($project && $risk->organization_id !== $project->organization_id) {
            $risk->organization_id = $project->organization_id;

            if ($persist) {
                $risk->saveQuietly();
            }
        }
    }
}
