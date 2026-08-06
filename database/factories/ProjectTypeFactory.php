<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\ProjectType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectType> */
class ProjectTypeFactory extends Factory
{
    protected $model = ProjectType::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'organization_id' => Organization::factory(),
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'description' => fake()->optional()->sentence(),
            'default_duration' => fake()->numberBetween(14, 365),
            'color' => fake()->hexColor(),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_system' => false,
            'is_active' => true,
        ];
    }
}
