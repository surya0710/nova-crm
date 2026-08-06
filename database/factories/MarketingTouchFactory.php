<?php

namespace Database\Factories;

use App\Models\MarketingSession;
use App\Models\MarketingTouch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingTouch>
 */
class MarketingTouchFactory extends Factory
{
    protected $model = MarketingTouch::class;

    public function definition(): array
    {
        return [
            'session_id' => MarketingSession::factory(),
            'occurred_at' => fake()->dateTimeBetween('-7 days'),
            'channel' => fake()->randomElement(['organic_search', 'paid_search', 'paid_social', 'direct', 'referral']),
            'source' => fake()->randomElement(['google', 'meta', 'linkedin', 'newsletter']),
            'medium' => fake()->randomElement(['cpc', 'organic', 'email', 'referral']),
            'campaign' => fake()->optional()->slug(3),
            'content' => fake()->optional()->slug(2),
            'term' => fake()->optional()->words(2, true),
            'landing_page' => fake()->url(),
            'referrer' => fake()->optional()->url(),
        ];
    }
}
