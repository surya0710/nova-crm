<?php

namespace Database\Factories;

use App\Models\MarketingProviderWebhookEvent;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MarketingProviderWebhookEvent>
 */
class MarketingProviderWebhookEventFactory extends Factory
{
    protected $model = MarketingProviderWebhookEvent::class;

    public function definition(): array
    {
        return [
            'organization_id' => null,
            'provider' => 'meta',
            'event_type' => 'leadgen',
            'delivery_id' => hash('sha256', Str::uuid()->toString()),
            'payload' => [
                'object' => 'page',
                'entry' => [],
            ],
            'signature' => 'sha256='.hash('sha256', 'test'),
            'received_at' => now(),
            'processed_at' => null,
            'processing_status' => MarketingProviderWebhookEvent::STATUS_RECEIVED,
        ];
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn () => [
            'organization_id' => $organization->id,
        ]);
    }

    public function verification(): static
    {
        return $this->state(fn () => [
            'event_type' => MarketingProviderWebhookEvent::EVENT_VERIFICATION,
            'delivery_id' => 'verify-'.hash('sha256', Str::uuid()->toString()),
            'payload' => ['hub.mode' => 'subscribe'],
            'signature' => null,
            'processing_status' => MarketingProviderWebhookEvent::STATUS_VERIFIED,
            'processed_at' => now(),
        ]);
    }

    public function duplicate(): static
    {
        return $this->state(fn () => [
            'processing_status' => MarketingProviderWebhookEvent::STATUS_DUPLICATE,
        ]);
    }
}
