<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\MarketingAttribution;
use App\Models\MarketingSession;
use App\Models\MarketingTouch;
use App\Models\MarketingVisitor;
use App\Models\Opportunity;
use Illuminate\Support\Facades\DB;

/**
 * Single write authority for marketing attribution (P7B.4).
 *
 * Attribution is a relationship between Marketing identity and CRM entities.
 * Marketing metadata stays on touches; CRM tables receive only FKs here.
 */
class MarketingAttributionService
{
    public function __construct(
        protected MarketingTrackingService $tracking,
    ) {}

    /**
     * Attribute a newly created lead to an anonymous visitor when one can be
     * resolved. No visitor → no attribution; lead creation is never blocked.
     *
     * @param  array{visitor_uuid?: string|null, session_uuid?: string|null}  $signals
     */
    public function attributeLead(Lead $lead, array $signals = []): ?MarketingAttribution
    {
        $existing = $this->findPrimaryForLead($lead);

        if ($existing) {
            return $existing;
        }

        $visitorUuid = $signals['visitor_uuid'] ?? $this->visitorUuidFromRequest();
        $sessionUuid = $signals['session_uuid'] ?? $this->sessionUuidFromRequest();

        if (! $visitorUuid) {
            return null;
        }

        $visitor = $this->tracking->findVisitor($visitorUuid);

        if (! $visitor) {
            return null;
        }

        return $this->attributeVisitorToLead($visitor, $lead, $sessionUuid);
    }

    /**
     * Create the first_touch primary attribution linking a visitor to a lead.
     * Resolves visitor ownership to the lead's organization.
     */
    public function attributeVisitorToLead(
        MarketingVisitor $visitor,
        Lead $lead,
        ?string $sessionUuid = null,
    ): ?MarketingAttribution {
        if ($this->findPrimaryForLead($lead)) {
            return $this->findPrimaryForLead($lead);
        }

        if ($this->findPrimaryForVisitor($visitor)) {
            // One visitor → one primary lead attribution.
            return null;
        }

        if (! $this->canClaimVisitor($visitor, $lead->organization_id)) {
            return null;
        }

        $session = $this->resolveFirstTouchSession($visitor, $sessionUuid);

        return DB::transaction(function () use ($visitor, $lead, $session) {
            $this->claimVisitorOwnership($visitor, $lead->organization_id);

            return MarketingAttribution::query()->create([
                'organization_id' => $lead->organization_id,
                'marketing_visitor_id' => $visitor->id,
                'marketing_session_id' => $session?->id,
                'lead_id' => $lead->id,
                'attribution_model' => config('marketing.attribution.default_model', MarketingAttribution::MODEL_FIRST_TOUCH),
                'is_primary' => true,
                'attributed_at' => now(),
            ]);
        });
    }

    /**
     * Propagate an existing lead attribution onto Customer / Opportunity
     * during conversion. Updates the same attribution row — no duplicates.
     */
    public function propagateToConversion(
        Lead $lead,
        Customer $customer,
        ?Opportunity $opportunity = null,
    ): ?MarketingAttribution {
        $attribution = $this->findPrimaryForLead($lead);

        if (! $attribution) {
            return null;
        }

        if ($attribution->organization_id !== $lead->organization_id
            || $customer->organization_id !== $lead->organization_id
            || ($opportunity && $opportunity->organization_id !== $lead->organization_id)) {
            return null;
        }

        $attribution->update([
            'customer_id' => $customer->id,
            'opportunity_id' => $opportunity?->id,
        ]);

        return $attribution->fresh();
    }

    public function findPrimaryForLead(Lead $lead): ?MarketingAttribution
    {
        return MarketingAttribution::query()
            ->where('lead_id', $lead->id)
            ->where('is_primary', true)
            ->first();
    }

    public function findPrimaryForVisitor(MarketingVisitor $visitor): ?MarketingAttribution
    {
        return MarketingAttribution::query()
            ->where('marketing_visitor_id', $visitor->id)
            ->where('is_primary', true)
            ->first();
    }

    public function findForCustomer(Customer $customer): ?MarketingAttribution
    {
        return MarketingAttribution::query()
            ->where('customer_id', $customer->id)
            ->where('is_primary', true)
            ->first();
    }

    public function findForOpportunity(Opportunity $opportunity): ?MarketingAttribution
    {
        return MarketingAttribution::query()
            ->where('opportunity_id', $opportunity->id)
            ->where('is_primary', true)
            ->first();
    }

    /**
     * First-touch session: earliest touch's session for the visitor, falling
     * back to an explicitly supplied session UUID, then the earliest session.
     */
    protected function resolveFirstTouchSession(MarketingVisitor $visitor, ?string $sessionUuid): ?MarketingSession
    {
        $firstTouch = MarketingTouch::query()
            ->whereHas('session', fn ($query) => $query->where('visitor_id', $visitor->id))
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->first();

        if ($firstTouch) {
            return $firstTouch->session;
        }

        if ($sessionUuid) {
            $session = $this->tracking->findSession($sessionUuid);

            if ($session && $session->visitor_id === $visitor->id) {
                return $session;
            }
        }

        return MarketingSession::query()
            ->where('visitor_id', $visitor->id)
            ->orderBy('started_at')
            ->orderBy('id')
            ->first();
    }

    protected function canClaimVisitor(MarketingVisitor $visitor, int $organizationId): bool
    {
        return $visitor->organization_id === null
            || (int) $visitor->organization_id === $organizationId;
    }

    protected function claimVisitorOwnership(MarketingVisitor $visitor, int $organizationId): void
    {
        if ($visitor->organization_id === null) {
            $visitor->update(['organization_id' => $organizationId]);
        }
    }

    protected function visitorUuidFromRequest(): ?string
    {
        $request = request();

        $fromAttribute = $request->attributes->get('marketing_visitor');

        if ($fromAttribute instanceof MarketingVisitor) {
            return $fromAttribute->visitor_uuid;
        }

        $cookie = $request->cookie(config('marketing.tracking.visitor_cookie'));

        return is_string($cookie) && $cookie !== '' ? $cookie : null;
    }

    protected function sessionUuidFromRequest(): ?string
    {
        $request = request();

        $fromAttribute = $request->attributes->get('marketing_session');

        if ($fromAttribute instanceof MarketingSession) {
            return $fromAttribute->session_uuid;
        }

        $cookie = $request->cookie(config('marketing.tracking.session_cookie'));

        return is_string($cookie) && $cookie !== '' ? $cookie : null;
    }
}
