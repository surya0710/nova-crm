<?php

namespace Database\Factories;

use App\Models\MarketingProvider;
use App\Models\MarketingProviderCredential;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingProviderCredential>
 */
class MarketingProviderCredentialFactory extends Factory
{
    protected $model = MarketingProviderCredential::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'marketing_provider_id' => MarketingProvider::factory(),
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'token_type' => 'Bearer',
            'scopes' => ['ads_read'],
            'configuration' => null,
            'expires_at' => now()->addHour(),
            'metadata' => null,
        ];
    }

    public function forProvider(MarketingProvider $provider): static
    {
        return $this->state(fn () => [
            'organization_id' => $provider->organization_id,
            'marketing_provider_id' => $provider->id,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subMinute(),
        ]);
    }
}
