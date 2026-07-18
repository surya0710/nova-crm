<?php

namespace Database\Factories;

use App\Models\MarketingProvider;
use App\Models\MarketingProviderLeadForm;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingProviderLeadForm>
 */
class MarketingProviderLeadFormFactory extends Factory
{
    protected $model = MarketingProviderLeadForm::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'marketing_provider_id' => MarketingProvider::factory(),
            'external_form_id' => (string) fake()->unique()->numerify('##########'),
            'external_page_id' => (string) fake()->numerify('##########'),
            'name' => fake()->words(3, true).' Form',
            'status' => MarketingProviderLeadForm::STATUS_ACTIVE,
            'locale' => 'en_US',
            'questions' => [
                ['id' => '1', 'key' => 'full_name', 'label' => 'Full Name', 'type' => 'FULL_NAME'],
                ['id' => '2', 'key' => 'email', 'label' => 'Email', 'type' => 'EMAIL'],
            ],
            'raw_metadata' => [
                'provider_status' => 'ACTIVE',
            ],
            'last_synced_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => MarketingProviderLeadForm::STATUS_INACTIVE,
        ]);
    }

    public function forProvider(MarketingProvider $provider): static
    {
        return $this->state(fn () => [
            'organization_id' => $provider->organization_id,
            'marketing_provider_id' => $provider->id,
        ]);
    }
}
