<?php

namespace Database\Factories;

use App\Models\InterviewStage;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InterviewStage> */
class InterviewStageFactory extends Factory
{
    protected $model = InterviewStage::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'organization_id' => Organization::factory(),
            'slug' => $slug,
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'sort_order' => fake()->numberBetween(0, 20),
            'is_default' => false,
            'is_active' => true,
        ];
    }
}
