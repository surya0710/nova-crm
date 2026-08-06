<?php

namespace Database\Factories;

use App\Models\MarketingVisitor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MarketingVisitor>
 */
class MarketingVisitorFactory extends Factory
{
    protected $model = MarketingVisitor::class;

    public function definition(): array
    {
        $seenAt = fake()->dateTimeBetween('-30 days');

        return [
            'organization_id' => null,
            'visitor_uuid' => (string) Str::uuid(),
            'first_seen_at' => $seenAt,
            'last_seen_at' => $seenAt,
            'first_ip' => fake()->ipv4(),
            'last_ip' => fake()->ipv4(),
            'first_user_agent' => fake()->userAgent(),
            'last_user_agent' => fake()->userAgent(),
        ];
    }
}
