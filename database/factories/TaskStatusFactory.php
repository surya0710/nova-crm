<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\TaskStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaskStatus> */
class TaskStatusFactory extends Factory
{
    protected $model = TaskStatus::class;

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

    public function default(): static
    {
        return $this->state(fn () => [
            'is_default' => true,
            'is_closed' => false,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'is_closed' => true,
            'is_default' => false,
        ]);
    }
}
