<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectDependency;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectDependency> */
class ProjectDependencyFactory extends Factory
{
    protected $model = ProjectDependency::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'predecessor_project_id' => Project::factory(),
            'successor_project_id' => Project::factory(),
            'dependency_type' => 'finish_to_start',
            'lag_days' => 0,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ProjectDependency $dependency) {
            $this->alignOrganization($dependency);
        })->afterCreating(function (ProjectDependency $dependency) {
            $this->alignOrganization($dependency, true);
        });
    }

    protected function alignOrganization(ProjectDependency $dependency, bool $persist = false): void
    {
        $predecessor = $dependency->predecessor_project_id
            ? Project::query()->find($dependency->predecessor_project_id)
            : null;

        if ($predecessor && $dependency->organization_id !== $predecessor->organization_id) {
            $dependency->organization_id = $predecessor->organization_id;

            if ($persist) {
                $dependency->saveQuietly();
            }
        }
    }
}
