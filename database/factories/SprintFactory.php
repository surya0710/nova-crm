<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Sprint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sprint>
 */
class SprintFactory extends Factory
{
    protected $model = Sprint::class;

    public function definition(): array
    {
        $start = now()->startOfWeek();

        return [
            'organization_id' => Organization::factory(),
            'name' => 'Sprint '.$this->faker->numberBetween(1, 50),
            'goal' => $this->faker->sentence(),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(13)->toDateString(),
            'status' => 'planned',
            'sort_order' => 0,
        ];
    }
}
