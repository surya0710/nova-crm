<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\ProjectLabel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectLabel> */
class ProjectLabelFactory extends Factory
{
    protected $model = ProjectLabel::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->unique()->words(2, true),
            'color' => fake()->hexColor(),
            'description' => fake()->optional()->sentence(),
            'is_system' => false,
        ];
    }

    public function system(): static
    {
        return $this->state(fn () => ['is_system' => true]);
    }
}
