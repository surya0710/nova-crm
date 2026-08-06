<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectBudget;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectBudget> */
class ProjectBudgetFactory extends Factory
{
    protected $model = ProjectBudget::class;

    public function definition(): array
    {
        $planned = fake()->randomFloat(2, 1000, 500000);
        $actual = fake()->randomFloat(2, 0, $planned);
        $forecast = fake()->randomFloat(2, $actual, $planned * 1.2);

        return [
            'organization_id' => Organization::factory(),
            'project_id' => Project::factory(),
            'name' => 'Primary Budget',
            'currency' => 'USD',
            'planned_total' => $planned,
            'actual_total' => $actual,
            'forecast_total' => $forecast,
            'variance_total' => round($planned - $actual, 2),
            'status' => 'draft',
            'notes' => fake()->optional()->sentence(),
            'metadata' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ProjectBudget $budget) {
            $this->alignOrganization($budget);
        })->afterCreating(function (ProjectBudget $budget) {
            $this->alignOrganization($budget, true);
        });
    }

    protected function alignOrganization(ProjectBudget $budget, bool $persist = false): void
    {
        $project = $budget->project_id
            ? Project::query()->find($budget->project_id)
            : null;

        if ($project && $budget->organization_id !== $project->organization_id) {
            $budget->organization_id = $project->organization_id;

            if ($persist) {
                $budget->saveQuietly();
            }
        }
    }
}
