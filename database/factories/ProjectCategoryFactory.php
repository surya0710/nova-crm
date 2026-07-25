<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\ProjectCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectCategory> */
class ProjectCategoryFactory extends Factory
{
    protected $model = ProjectCategory::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'organization_id' => Organization::factory(),
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'description' => fake()->optional()->sentence(),
            'color' => fake()->hexColor(),
            'icon' => fake()->randomElement(['code', 'rocket', 'building', 'cog', 'users']),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_system' => false,
            'is_active' => true,
        ];
    }
}
