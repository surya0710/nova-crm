<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\GoalCheckin;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GoalCheckin> */
class GoalCheckinFactory extends Factory
{
    protected $model = GoalCheckin::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'goal_id' => Goal::factory(),
            'summary' => fake()->sentence(),
            'progress' => fake()->sentence(),
            'risks' => null,
            'next_steps' => null,
            'checked_in_by' => User::factory(),
        ];
    }
}
