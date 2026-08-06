<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\MarketingProvider;
use App\Models\MarketingProviderImportedLead;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingProviderImportedLead>
 */
class MarketingProviderImportedLeadFactory extends Factory
{
    protected $model = MarketingProviderImportedLead::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'marketing_provider_id' => MarketingProvider::factory(),
            'lead_id' => null,
            'external_lead_id' => (string) fake()->unique()->numerify('############'),
            'external_form_id' => (string) fake()->numerify('##########'),
            'raw_payload' => [],
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

    public function forLead(Lead $lead): static
    {
        return $this->state(fn () => [
            'lead_id' => $lead->id,
            'organization_id' => $lead->organization_id,
        ]);
    }
}
