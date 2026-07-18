<?php

namespace Database\Factories;

use App\Models\MarketingProvider;
use App\Models\MarketingProviderSyncRun;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingProviderSyncRun>
 */
class MarketingProviderSyncRunFactory extends Factory
{
    protected $model = MarketingProviderSyncRun::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'marketing_provider_id' => MarketingProvider::factory(),
            'sync_type' => MarketingProviderSyncRun::TYPE_ASSET_DISCOVERY,
            'direction' => MarketingProviderSyncRun::DIRECTION_INBOUND,
            'status' => MarketingProviderSyncRun::STATUS_PENDING,
            'started_at' => null,
            'finished_at' => null,
            'records_processed' => 0,
            'records_succeeded' => 0,
            'records_failed' => 0,
            'message' => null,
            'metadata' => [],
        ];
    }

    public function forProvider(MarketingProvider $provider): static
    {
        return $this->state(fn () => [
            'organization_id' => $provider->organization_id,
            'marketing_provider_id' => $provider->id,
        ]);
    }

    public function running(): static
    {
        return $this->state(fn () => [
            'status' => MarketingProviderSyncRun::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }
}
