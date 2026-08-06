<?php

namespace Database\Factories;

use App\Models\MarketingConversion;
use App\Models\MarketingProvider;
use App\Models\MarketingProviderUploadedConversion;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingProviderUploadedConversion>
 */
class MarketingProviderUploadedConversionFactory extends Factory
{
    protected $model = MarketingProviderUploadedConversion::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'marketing_provider_id' => MarketingProvider::factory(),
            'marketing_conversion_id' => MarketingConversion::factory(),
            'external_event_id' => null,
            'provider_event_name' => 'Lead',
            'metadata' => [],
            'uploaded_at' => now(),
        ];
    }

    public function forProvider(MarketingProvider $provider): static
    {
        return $this->state(fn () => [
            'organization_id' => $provider->organization_id,
            'marketing_provider_id' => $provider->id,
        ]);
    }

    public function forConversion(MarketingConversion $conversion): static
    {
        return $this->state(fn () => [
            'organization_id' => $conversion->organization_id,
            'marketing_conversion_id' => $conversion->id,
        ]);
    }
}
