<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\MarketingAttribution;
use App\Models\MarketingSession;
use App\Models\MarketingVisitor;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingAttribution>
 */
class MarketingAttributionFactory extends Factory
{
    protected $model = MarketingAttribution::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'marketing_visitor_id' => MarketingVisitor::factory(),
            'marketing_session_id' => null,
            'lead_id' => null,
            'customer_id' => null,
            'opportunity_id' => null,
            'attribution_model' => MarketingAttribution::MODEL_FIRST_TOUCH,
            'is_primary' => true,
            'attributed_at' => now(),
        ];
    }

    public function forLead(Lead $lead): static
    {
        return $this->state(fn () => [
            'organization_id' => $lead->organization_id,
            'lead_id' => $lead->id,
        ]);
    }

    public function withSession(MarketingSession $session): static
    {
        return $this->state(fn () => [
            'marketing_visitor_id' => $session->visitor_id,
            'marketing_session_id' => $session->id,
        ]);
    }
}
