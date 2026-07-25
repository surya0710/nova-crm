<?php

namespace Database\Factories;

use App\Models\HrmsTeam;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HrmsTeam> */
class HrmsTeamFactory extends Factory
{
    protected $model = HrmsTeam::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(2, true).' Team',
            'code' => strtoupper(fake()->unique()->bothify('TEAM-###')),
            'is_active' => true,
        ];
    }
}
