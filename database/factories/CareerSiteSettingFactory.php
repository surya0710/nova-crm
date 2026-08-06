<?php

namespace Database\Factories;

use App\Models\CareerSiteSetting;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CareerSiteSetting> */
class CareerSiteSettingFactory extends Factory
{
    protected $model = CareerSiteSetting::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'about_us' => fake()->paragraph(),
            'benefits' => fake()->paragraph(),
            'is_published' => true,
        ];
    }
}
