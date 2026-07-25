<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\GoalProgressUpdate;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GoalProgressUpdate> */
class GoalProgressUpdateFactory extends Factory
{
    protected $model = GoalProgressUpdate::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'goal_id' => Goal::factory(),
            'progress_value' => 50,
            'achievement_percentage' => 50,
            'notes' => null,
            'updated_by' => User::factory(),
        ];
    }
}
