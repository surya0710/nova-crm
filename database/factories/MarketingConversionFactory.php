<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\MarketingAttribution;
use App\Models\MarketingConversion;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingConversion>
 */
class MarketingConversionFactory extends Factory
{
    protected $model = MarketingConversion::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'marketing_attribution_id' => MarketingAttribution::factory(),
            'lead_id' => null,
            'customer_id' => null,
            'opportunity_id' => null,
            'event_name' => MarketingConversion::LEAD_CREATED,
            'event_value' => null,
            'currency' => null,
            'metadata' => null,
            'occurred_at' => now(),
        ];
    }

    public function forLead(Lead $lead, MarketingAttribution $attribution): static
    {
        return $this->state(fn () => [
            'organization_id' => $lead->organization_id,
            'marketing_attribution_id' => $attribution->id,
            'lead_id' => $lead->id,
        ]);
    }
}
