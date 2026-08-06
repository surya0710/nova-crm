<?php

namespace Database\Factories;

use App\Models\NotificationPreference;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NotificationPreference> */
class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'in_app_enabled' => true,
            'email_enabled' => true,
            'digest_enabled' => false,
            'digest_frequency' => 'daily',
            'muted_projects' => null,
            'muted_tasks' => null,
            'event_preferences' => null,
            'channels' => null,
        ];
    }

    public function digest(): static
    {
        return $this->state(fn () => [
            'digest_enabled' => true,
            'digest_frequency' => fake()->randomElement(['daily', 'weekly']),
        ]);
    }
}
