<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\TaskPriority;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaskPriority> */
class TaskPriorityFactory extends Factory
{
    protected $model = TaskPriority::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'organization_id' => Organization::factory(),
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'color' => fake()->hexColor(),
            'level' => fake()->numberBetween(1, 4),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => [
            'is_default' => true,
            'level' => 2,
            'slug' => 'medium',
            'name' => 'Medium',
        ]);
    }
}
