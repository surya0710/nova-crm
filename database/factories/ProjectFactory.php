<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectLifecycleStage;
use App\Models\ProjectStatus;
use App\Models\ProjectType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Project> */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);
        $prefix = config('projects.number_prefix', 'PRJ');
        $padding = config('projects.number_padding', 4);

        return [
            'organization_id' => Organization::factory(),
            'category_id' => ProjectCategory::factory(),
            'project_type_id' => ProjectType::factory(),
            'status_id' => ProjectStatus::factory(),
            'lifecycle_stage_id' => ProjectLifecycleStage::factory(),
            'client_id' => null,
            'project_number' => $prefix.'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), $padding, '0', STR_PAD_LEFT),
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->optional()->paragraph(),
            'objective' => fake()->optional()->sentence(),
            'owner_id' => User::factory(),
            'manager_id' => User::factory(),
            'department_id' => null,
            'priority' => fake()->randomElement(array_keys(config('projects.priorities'))),
            'start_date' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'planned_end_date' => fake()->optional()->dateTimeBetween('now', '+6 months'),
            'actual_end_date' => null,
            'estimated_budget' => fake()->optional()->randomFloat(2, 10000, 500000),
            'actual_budget' => null,
            'completion_percentage' => fake()->numberBetween(0, 100),
            'metadata' => null,
            'settings' => null,
            'is_archived' => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Project $project) {
            $this->alignOrganization($project);
        })->afterCreating(function (Project $project) {
            $this->alignOrganization($project, true);
        });
    }

    protected function alignOrganization(Project $project, bool $persist = false): void
    {
        $category = $project->category_id
            ? ProjectCategory::query()->find($project->category_id)
            : null;

        if ($category && $project->organization_id !== $category->organization_id) {
            $project->organization_id = $category->organization_id;

            if ($persist) {
                $project->saveQuietly();
            }
        }
    }

    public function archived(): static
    {
        return $this->state(fn () => ['is_archived' => true]);
    }
}
