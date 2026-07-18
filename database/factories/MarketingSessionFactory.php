<?php

namespace Database\Factories;

use App\Models\MarketingSession;
use App\Models\MarketingVisitor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MarketingSession>
 */
class MarketingSessionFactory extends Factory
{
    protected $model = MarketingSession::class;

    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-7 days');

        return [
            'visitor_id' => MarketingVisitor::factory(),
            'session_uuid' => (string) Str::uuid(),
            'started_at' => $startedAt,
            'ended_at' => null,
            'last_activity_at' => $startedAt,
            'landing_page' => fake()->url(),
            'referrer' => fake()->optional()->url(),
            'user_agent' => fake()->userAgent(),
            'ip_address' => fake()->ipv4(),
            'device_type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
            'browser' => fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'operating_system' => fake()->randomElement(['Windows', 'macOS', 'Linux', 'iOS', 'Android']),
        ];
    }
}
