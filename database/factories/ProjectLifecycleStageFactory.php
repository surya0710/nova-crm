<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\ProjectLifecycleStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectLifecycleStage> */
class ProjectLifecycleStageFactory extends Factory
{
    protected $model = ProjectLifecycleStage::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'organization_id' => Organization::factory(),
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'description' => fake()->optional()->sentence(),
            'sequence' => fake()->numberBetween(1, 10),
            'color' => fake()->hexColor(),
            'is_default' => false,
        ];
    }
}
