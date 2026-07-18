<?php

namespace Database\Factories;

use App\Models\MarketingProvider;
use App\Models\MarketingProviderLeadImportRun;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingProviderLeadImportRun>
 */
class MarketingProviderLeadImportRunFactory extends Factory
{
    protected $model = MarketingProviderLeadImportRun::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'marketing_provider_id' => MarketingProvider::factory(),
            'triggered_by' => User::factory(),
            'imported_count' => 0,
            'skipped_count' => 0,
            'failed_count' => 0,
            'status' => MarketingProviderLeadImportRun::STATUS_COMPLETED,
            'message' => null,
            'metadata' => [],
            'imported_at' => now(),
        ];
    }

    public function forProvider(MarketingProvider $provider): static
    {
        return $this->state(fn () => [
            'organization_id' => $provider->organization_id,
            'marketing_provider_id' => $provider->id,
        ]);
    }
}
