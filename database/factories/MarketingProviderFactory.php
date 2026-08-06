<?php

namespace Database\Factories;

use App\Models\MarketingProvider;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingProvider>
 */
class MarketingProviderFactory extends Factory
{
    protected $model = MarketingProvider::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'slug' => 'test_provider',
            'display_name' => 'Test Provider',
            'status' => MarketingProvider::STATUS_DISCONNECTED,
            'external_account_id' => null,
            'capabilities' => [],
            'metadata' => null,
            'last_error' => null,
            'last_synced_at' => null,
            'last_health_at' => null,
            'connected_at' => null,
            'disconnected_at' => null,
        ];
    }

    public function connected(): static
    {
        return $this->state(fn () => [
            'status' => MarketingProvider::STATUS_CONNECTED,
            'connected_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => MarketingProvider::STATUS_EXPIRED,
            'last_error' => 'Credentials expired',
        ]);
    }

    public function errored(string $message = 'Provider error'): static
    {
        return $this->state(fn () => [
            'status' => MarketingProvider::STATUS_ERROR,
            'last_error' => $message,
        ]);
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn () => [
            'organization_id' => $organization->id,
        ]);
    }
}
