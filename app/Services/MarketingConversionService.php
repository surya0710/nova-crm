<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\MarketingAttribution;
use App\Models\MarketingConversion;
use App\Models\Opportunity;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Single write authority for marketing conversion events (P7B.5).
 *
 * Conversions are immutable business events resolved through attribution.
 * No attribution → no conversion. CRM behavior is never blocked.
 */
class MarketingConversionService
{
    public function __construct(
        protected MarketingAttributionService $attribution,
    ) {}

    /**
     * @param  array{
     *     lead?: Lead|null,
     *     customer?: Customer|null,
     *     opportunity?: Opportunity|null,
     *     event_value?: float|string|null,
     *     currency?: string|null,
     *     metadata?: array<string, mixed>|null,
     *     occurred_at?: CarbonInterface|null,
     * }  $context
     */
    public function record(string $eventName, array $context = []): ?MarketingConversion
    {
        $eventName = $this->assertSupportedEvent($eventName);

        $lead = $context['lead'] ?? null;
        $customer = $context['customer'] ?? null;
        $opportunity = $context['opportunity'] ?? null;

        $attribution = $this->resolveAttribution($lead, $customer, $opportunity);

        if (! $attribution) {
            return null;
        }

        if ($this->alreadyRecorded($eventName, $lead, $customer, $opportunity)) {
            return $this->findExisting($eventName, $lead, $customer, $opportunity);
        }

        $organizationId = $attribution->organization_id;

        if (! $this->entitiesBelongToOrganization($organizationId, $lead, $customer, $opportunity)) {
            return null;
        }

        return MarketingConversion::query()->create([
            'organization_id' => $organizationId,
            'marketing_attribution_id' => $attribution->id,
            'lead_id' => $lead?->id ?? $attribution->lead_id,
            'customer_id' => $customer?->id ?? $attribution->customer_id,
            'opportunity_id' => $opportunity?->id ?? $attribution->opportunity_id,
            'event_name' => $eventName,
            'event_value' => $context['event_value'] ?? null,
            'currency' => $context['currency'] ?? null,
            'metadata' => $this->normalizeMetadata($context['metadata'] ?? null),
            'occurred_at' => $context['occurred_at'] ?? now(),
        ]);
    }

    public function recordLeadCreated(Lead $lead): ?MarketingConversion
    {
        return $this->record(MarketingConversion::LEAD_CREATED, [
            'lead' => $lead,
            'metadata' => [
                'lead_source' => $lead->source,
            ],
        ]);
    }

    public function recordLeadConverted(Lead $lead, Customer $customer, ?Opportunity $opportunity = null): ?MarketingConversion
    {
        return $this->record(MarketingConversion::LEAD_CONVERTED, [
            'lead' => $lead,
            'customer' => $customer,
            'opportunity' => $opportunity,
        ]);
    }

    public function recordCustomerCreated(Lead $lead, Customer $customer): ?MarketingConversion
    {
        return $this->record(MarketingConversion::CUSTOMER_CREATED, [
            'lead' => $lead,
            'customer' => $customer,
        ]);
    }

    public function recordOpportunityCreated(Lead $lead, Customer $customer, Opportunity $opportunity): ?MarketingConversion
    {
        return $this->record(MarketingConversion::OPPORTUNITY_CREATED, [
            'lead' => $lead,
            'customer' => $customer,
            'opportunity' => $opportunity,
            'metadata' => [
                'stage' => $opportunity->stage,
            ],
        ]);
    }

    public function recordOpportunityWon(Opportunity $opportunity): ?MarketingConversion
    {
        return $this->record(MarketingConversion::OPPORTUNITY_WON, [
            'lead' => $opportunity->lead,
            'customer' => $opportunity->customer,
            'opportunity' => $opportunity,
            'event_value' => $opportunity->amount,
            'currency' => $opportunity->currency,
            'metadata' => [
                'stage' => $opportunity->stage,
                'won_at' => $opportunity->won_at?->toDateString(),
            ],
            'occurred_at' => $opportunity->won_at?->startOfDay() ?? now(),
        ]);
    }

    /**
     * @return Collection<int, MarketingConversion>
     */
    public function historyForLead(Lead $lead): Collection
    {
        return MarketingConversion::query()
            ->where('lead_id', $lead->id)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, MarketingConversion>
     */
    public function historyForAttribution(MarketingAttribution $attribution): Collection
    {
        return MarketingConversion::query()
            ->where('marketing_attribution_id', $attribution->id)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return list<string>
     */
    public function supportedEvents(): array
    {
        return config('marketing.conversions.supported_events', MarketingConversion::SUPPORTED_EVENTS);
    }

    protected function assertSupportedEvent(string $eventName): string
    {
        if (! in_array($eventName, $this->supportedEvents(), true)) {
            throw new InvalidArgumentException("Unsupported marketing conversion event [{$eventName}].");
        }

        return $eventName;
    }

    protected function resolveAttribution(
        ?Lead $lead,
        ?Customer $customer,
        ?Opportunity $opportunity,
    ): ?MarketingAttribution {
        if ($lead) {
            $attribution = $this->attribution->findPrimaryForLead($lead);

            if ($attribution) {
                return $attribution;
            }
        }

        if ($customer) {
            $attribution = $this->attribution->findForCustomer($customer);

            if ($attribution) {
                return $attribution;
            }
        }

        if ($opportunity) {
            return $this->attribution->findForOpportunity($opportunity);
        }

        return null;
    }

    protected function alreadyRecorded(
        string $eventName,
        ?Lead $lead,
        ?Customer $customer,
        ?Opportunity $opportunity,
    ): bool {
        return $this->findExisting($eventName, $lead, $customer, $opportunity) !== null;
    }

    protected function findExisting(
        string $eventName,
        ?Lead $lead,
        ?Customer $customer,
        ?Opportunity $opportunity,
    ): ?MarketingConversion {
        $query = MarketingConversion::query()->where('event_name', $eventName);

        return match ($eventName) {
            MarketingConversion::LEAD_CREATED,
            MarketingConversion::LEAD_CONVERTED => $lead
                ? $query->where('lead_id', $lead->id)->first()
                : null,
            MarketingConversion::CUSTOMER_CREATED => $customer
                ? $query->where('customer_id', $customer->id)->first()
                : null,
            MarketingConversion::OPPORTUNITY_CREATED,
            MarketingConversion::OPPORTUNITY_WON => $opportunity
                ? $query->where('opportunity_id', $opportunity->id)->first()
                : null,
            default => null,
        };
    }

    protected function entitiesBelongToOrganization(
        int $organizationId,
        ?Lead $lead,
        ?Customer $customer,
        ?Opportunity $opportunity,
    ): bool {
        if ($lead && (int) $lead->organization_id !== $organizationId) {
            return false;
        }

        if ($customer && (int) $customer->organization_id !== $organizationId) {
            return false;
        }

        if ($opportunity && (int) $opportunity->organization_id !== $organizationId) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>|null
     */
    protected function normalizeMetadata(?array $metadata): ?array
    {
        if ($metadata === null || $metadata === []) {
            return null;
        }

        // Strip any tracking fields that must never live on conversion rows.
        unset(
            $metadata['utm_source'],
            $metadata['utm_medium'],
            $metadata['utm_campaign'],
            $metadata['utm_term'],
            $metadata['utm_content'],
            $metadata['gclid'],
            $metadata['fbclid'],
            $metadata['msclkid'],
            $metadata['channel'],
        );

        return $metadata === [] ? null : $metadata;
    }
}
