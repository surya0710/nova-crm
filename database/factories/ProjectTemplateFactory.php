<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\ProjectTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ProjectTemplate> */
class ProjectTemplateFactory extends Factory
{
    protected $model = ProjectTemplate::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'organization_id' => Organization::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->optional()->paragraph(),
            'category' => fake()->optional()->word(),
            'industry' => fake()->optional()->word(),
            'department_id' => null,
            'source_project_id' => null,
            'created_by' => User::factory(),
            'is_system' => false,
            'is_favorite' => false,
            'version' => 1,
            'usage_count' => 0,
            'defaults' => null,
            'metadata' => null,
        ];
    }

    public function system(): static
    {
        return $this->state(fn () => [
            'organization_id' => null,
            'is_system' => true,
            'created_by' => null,
        ]);
    }

    public function favorite(): static
    {
        return $this->state(fn () => ['is_favorite' => true]);
    }
}
