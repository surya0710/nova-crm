<?php

namespace Database\Factories;

use App\Models\HrmsAnnouncement;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HrmsAnnouncement> */
class HrmsAnnouncementFactory extends Factory
{
    protected $model = HrmsAnnouncement::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'target_audience' => 'everyone',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }
}
