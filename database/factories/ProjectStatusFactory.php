<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectStatus> */
class ProjectStatusFactory extends Factory
{
    protected $model = ProjectStatus::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'organization_id' => Organization::factory(),
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'color' => fake()->hexColor(),
            'is_default' => false,
            'is_closed' => false,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
