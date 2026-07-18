<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\MarketingConversion;
use App\Models\Opportunity;
use App\Models\Organization;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * Historical attribution backfill orchestrator (P7B.6).
 *
 * Maintenance-only. All writes go through MarketingAttributionService and
 * MarketingConversionService. Never mutates touches, sessions, or visitors
 * directly. Never overwrites existing attribution.
 */
class MarketingBackfillService
{
    public function __construct(
        protected MarketingAttributionService $attribution,
        protected MarketingConversionService $conversions,
        protected MarketingTrackingService $tracking,
    ) {}

    /**
     * @param  array{
     *     organization_id?: int|null,
     *     lead_id?: int|null,
     *     customer_id?: int|null,
     *     opportunity_id?: int|null,
     *     dry_run?: bool,
     *     chunk?: int,
     *     force?: bool,
     * }  $options
     * @return array{
     *     processed: int,
     *     skipped: int,
     *     attributed: int,
     *     conversions_replayed: int,
     *     failed: int,
     *     dry_run: bool,
     *     would_attribute: int,
     *     would_replay: int,
     * }
     */
    public function run(array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $force = (bool) ($options['force'] ?? false);
        $chunk = max(1, (int) ($options['chunk'] ?? config('marketing.backfill.chunk_size', 100)));

        $stats = $this->emptyStats($dryRun);

        if (! empty($options['lead_id'])) {
            $this->processLeadId((int) $options['lead_id'], $options['organization_id'] ?? null, $dryRun, $force, $stats);

            return $stats;
        }

        if (! empty($options['customer_id'])) {
            $this->processCustomerId((int) $options['customer_id'], $options['organization_id'] ?? null, $dryRun, $force, $stats);

            return $stats;
        }

        if (! empty($options['opportunity_id'])) {
            $this->processOpportunityId((int) $options['opportunity_id'], $options['organization_id'] ?? null, $dryRun, $force, $stats);

            return $stats;
        }

        $organizationId = $options['organization_id'] ?? null;

        if (! $organizationId) {
            throw new \InvalidArgumentException('An organization_id is required for bulk backfill.');
        }

        Organization::query()->findOrFail($organizationId);

        if ($force) {
            $this->resetCursor($organizationId, 'leads');
            $this->resetCursor($organizationId, 'customers');
            $this->resetCursor($organizationId, 'opportunities');
        }

        $this->chunkLeads($organizationId, $chunk, $dryRun, $force, $stats);
        $this->chunkCustomers($organizationId, $chunk, $dryRun, $force, $stats);
        $this->chunkOpportunities($organizationId, $chunk, $dryRun, $force, $stats);

        return $stats;
    }

    /**
     * @param  array<string, int|bool>  $stats
     */
    protected function processLeadId(int $leadId, ?int $organizationId, bool $dryRun, bool $force, array &$stats): void
    {
        $query = Lead::withoutGlobalScopes()->whereKey($leadId);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        $lead = $query->first();

        if (! $lead) {
            $stats['failed']++;

            return;
        }

        $this->backfillLead($lead, $dryRun, $force, $stats);
    }

    /**
     * @param  array<string, int|bool>  $stats
     */
    protected function processCustomerId(int $customerId, ?int $organizationId, bool $dryRun, bool $force, array &$stats): void
    {
        $query = Customer::withoutGlobalScopes()->whereKey($customerId);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        $customer = $query->first();

        if (! $customer) {
            $stats['failed']++;

            return;
        }

        $this->backfillCustomer($customer, $dryRun, $force, $stats);
    }

    /**
     * @param  array<string, int|bool>  $stats
     */
    protected function processOpportunityId(int $opportunityId, ?int $organizationId, bool $dryRun, bool $force, array &$stats): void
    {
        $query = Opportunity::withoutGlobalScopes()->whereKey($opportunityId);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        $opportunity = $query->first();

        if (! $opportunity) {
            $stats['failed']++;

            return;
        }

        $this->backfillOpportunity($opportunity, $dryRun, $force, $stats);
    }

    /**
     * @param  array<string, int|bool>  $stats
     */
    protected function chunkLeads(int $organizationId, int $chunk, bool $dryRun, bool $force, array &$stats): void
    {
        $afterId = $dryRun ? 0 : $this->cursor($organizationId, 'leads');

        do {
            $leads = Lead::withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->limit($chunk)
                ->get();

            if ($leads->isEmpty()) {
                break;
            }

            foreach ($leads as $lead) {
                $this->backfillLead($lead, $dryRun, $force, $stats);
                $afterId = $lead->id;
            }

            if (! $dryRun) {
                $this->storeCursor($organizationId, 'leads', $afterId);
            }
        } while ($leads->count() === $chunk);
    }

    /**
     * @param  array<string, int|bool>  $stats
     */
    protected function chunkCustomers(int $organizationId, int $chunk, bool $dryRun, bool $force, array &$stats): void
    {
        $afterId = $dryRun ? 0 : $this->cursor($organizationId, 'customers');

        do {
            $customers = Customer::withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->limit($chunk)
                ->get();

            if ($customers->isEmpty()) {
                break;
            }

            foreach ($customers as $customer) {
                $this->backfillCustomer($customer, $dryRun, $force, $stats);
                $afterId = $customer->id;
            }

            if (! $dryRun) {
                $this->storeCursor($organizationId, 'customers', $afterId);
            }
        } while ($customers->count() === $chunk);
    }

    /**
     * @param  array<string, int|bool>  $stats
     */
    protected function chunkOpportunities(int $organizationId, int $chunk, bool $dryRun, bool $force, array &$stats): void
    {
        $afterId = $dryRun ? 0 : $this->cursor($organizationId, 'opportunities');

        do {
            $opportunities = Opportunity::withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->limit($chunk)
                ->get();

            if ($opportunities->isEmpty()) {
                break;
            }

            foreach ($opportunities as $opportunity) {
                $this->backfillOpportunity($opportunity, $dryRun, $force, $stats);
                $afterId = $opportunity->id;
            }

            if (! $dryRun) {
                $this->storeCursor($organizationId, 'opportunities', $afterId);
            }
        } while ($opportunities->count() === $chunk);
    }

    /**
     * @param  array<string, int|bool>  $stats
     */
    protected function backfillLead(Lead $lead, bool $dryRun, bool $force, array &$stats): void
    {
        $stats['processed']++;

        try {
            $existing = $this->attribution->findPrimaryForLead($lead);

            if ($existing) {
                if ($force) {
                    $replayed = $this->replayConversions($lead, $dryRun);
                    $stats['skipped']++;
                    $stats[$dryRun ? 'would_replay' : 'conversions_replayed'] += $replayed;
                } else {
                    $stats['skipped']++;
                }

                return;
            }

            $signals = $this->resolveLeadSignals($lead);

            if ($signals === null) {
                $stats['skipped']++;

                return;
            }

            if ($dryRun) {
                $stats['would_attribute']++;
                $stats['would_replay'] += $this->countMissingConversions($lead);

                return;
            }

            $attribution = $this->attribution->attributeLead($lead, $signals);

            if (! $attribution) {
                $stats['failed']++;

                return;
            }

            $stats['attributed']++;
            $stats['conversions_replayed'] += $this->replayConversions($lead, false);
        } catch (Throwable $e) {
            report($e);
            $stats['failed']++;
        }
    }

    /**
     * @param  array<string, int|bool>  $stats
     */
    protected function backfillCustomer(Customer $customer, bool $dryRun, bool $force, array &$stats): void
    {
        $stats['processed']++;

        try {
            if ($this->attribution->findForCustomer($customer)) {
                if ($force && $customer->lead_id) {
                    $lead = Lead::withoutGlobalScopes()->find($customer->lead_id);
                    if ($lead) {
                        $replayed = $this->replayConversions($lead, $dryRun);
                        $stats[$dryRun ? 'would_replay' : 'conversions_replayed'] += $replayed;
                    }
                }
                $stats['skipped']++;

                return;
            }

            if (! $customer->lead_id) {
                $stats['skipped']++;

                return;
            }

            $lead = Lead::withoutGlobalScopes()
                ->where('organization_id', $customer->organization_id)
                ->whereKey($customer->lead_id)
                ->first();

            if (! $lead) {
                $stats['failed']++;

                return;
            }

            $attribution = $this->attribution->findPrimaryForLead($lead);

            if (! $attribution) {
                $signals = $this->resolveLeadSignals($lead);

                if ($signals === null) {
                    $stats['skipped']++;

                    return;
                }

                if ($dryRun) {
                    $stats['would_attribute']++;
                    $stats['would_replay'] += $this->countMissingConversions($lead);

                    return;
                }

                $attribution = $this->attribution->attributeLead($lead, $signals);

                if (! $attribution) {
                    $stats['failed']++;

                    return;
                }

                $stats['attributed']++;
            } elseif ($dryRun) {
                // Existing lead attribution link — would propagate + replay only.
                $stats['would_replay'] += $this->countMissingConversions($lead);
                $stats['skipped']++;

                return;
            }

            $opportunity = Opportunity::withoutGlobalScopes()
                ->where('organization_id', $lead->organization_id)
                ->where('lead_id', $lead->id)
                ->orderBy('id')
                ->first();

            if (! $dryRun) {
                $this->attribution->propagateToConversion($lead, $customer, $opportunity);
                $stats['conversions_replayed'] += $this->replayConversions($lead, false);
            }
        } catch (Throwable $e) {
            report($e);
            $stats['failed']++;
        }
    }

    /**
     * @param  array<string, int|bool>  $stats
     */
    protected function backfillOpportunity(Opportunity $opportunity, bool $dryRun, bool $force, array &$stats): void
    {
        $stats['processed']++;

        try {
            if ($this->attribution->findForOpportunity($opportunity)) {
                if ($force && $opportunity->lead_id) {
                    $lead = Lead::withoutGlobalScopes()->find($opportunity->lead_id);
                    if ($lead) {
                        $replayed = $this->replayConversions($lead, $dryRun);
                        $stats[$dryRun ? 'would_replay' : 'conversions_replayed'] += $replayed;
                    }
                }
                $stats['skipped']++;

                return;
            }

            if (! $opportunity->lead_id) {
                $stats['skipped']++;

                return;
            }

            $lead = Lead::withoutGlobalScopes()
                ->where('organization_id', $opportunity->organization_id)
                ->whereKey($opportunity->lead_id)
                ->first();

            if (! $lead) {
                $stats['failed']++;

                return;
            }

            $attribution = $this->attribution->findPrimaryForLead($lead);

            if (! $attribution) {
                $signals = $this->resolveLeadSignals($lead);

                if ($signals === null) {
                    $stats['skipped']++;

                    return;
                }

                if ($dryRun) {
                    $stats['would_attribute']++;
                    $stats['would_replay'] += $this->countMissingConversions($lead);

                    return;
                }

                $attribution = $this->attribution->attributeLead($lead, $signals);

                if (! $attribution) {
                    $stats['failed']++;

                    return;
                }

                $stats['attributed']++;
            } elseif ($dryRun) {
                $stats['would_replay'] += $this->countMissingConversions($lead);
                $stats['skipped']++;

                return;
            }

            $customer = $opportunity->customer_id
                ? Customer::withoutGlobalScopes()
                    ->where('organization_id', $opportunity->organization_id)
                    ->whereKey($opportunity->customer_id)
                    ->first()
                : ($lead->customer()->withoutGlobalScopes()->first());

            if ($customer && ! $dryRun) {
                $this->attribution->propagateToConversion($lead, $customer, $opportunity);
                $stats['conversions_replayed'] += $this->replayConversions($lead, false);
            } elseif (! $customer) {
                $stats['skipped']++;
            }
        } catch (Throwable $e) {
            report($e);
            $stats['failed']++;
        }
    }

    /**
     * Deterministic signal resolution only: visitor_uuid, then session_uuid.
     *
     * @return array{visitor_uuid?: string, session_uuid?: string}|null
     */
    protected function resolveLeadSignals(Lead $lead): ?array
    {
        $fields = $lead->custom_fields ?? [];
        $visitorField = config('marketing.backfill.visitor_uuid_field', 'visitor_uuid');
        $sessionField = config('marketing.backfill.session_uuid_field', 'session_uuid');

        $visitorUuid = $this->normalizeUuid($fields[$visitorField] ?? null);
        $sessionUuid = $this->normalizeUuid($fields[$sessionField] ?? null);

        if ($visitorUuid) {
            $visitor = $this->tracking->findVisitor($visitorUuid);

            if (! $visitor) {
                return null;
            }

            if ($visitor->organization_id !== null && (int) $visitor->organization_id !== (int) $lead->organization_id) {
                return null;
            }

            $signals = ['visitor_uuid' => $visitorUuid];

            if ($sessionUuid) {
                $signals['session_uuid'] = $sessionUuid;
            }

            return $signals;
        }

        if ($sessionUuid) {
            $session = $this->tracking->findSession($sessionUuid);

            if (! $session) {
                return null;
            }

            $visitor = $session->visitor;

            if (! $visitor) {
                return null;
            }

            if ($visitor->organization_id !== null && (int) $visitor->organization_id !== (int) $lead->organization_id) {
                return null;
            }

            return [
                'visitor_uuid' => $visitor->visitor_uuid,
                'session_uuid' => $sessionUuid,
            ];
        }

        return null;
    }

    protected function replayConversions(Lead $lead, bool $dryRun): int
    {
        if ($dryRun) {
            return $this->countMissingConversions($lead);
        }

        $before = $this->conversionCountForLead($lead);

        $this->conversions->recordLeadCreated($lead);

        $customer = Customer::withoutGlobalScopes()
            ->where('organization_id', $lead->organization_id)
            ->where('lead_id', $lead->id)
            ->first();

        $opportunity = Opportunity::withoutGlobalScopes()
            ->where('organization_id', $lead->organization_id)
            ->where('lead_id', $lead->id)
            ->orderBy('id')
            ->first();

        if ($customer) {
            $this->attribution->propagateToConversion($lead, $customer, $opportunity);
            $this->conversions->recordLeadConverted($lead, $customer, $opportunity);
            $this->conversions->recordCustomerCreated($lead, $customer);

            if ($opportunity) {
                $this->conversions->recordOpportunityCreated($lead, $customer, $opportunity);

                if ($opportunity->isWon()) {
                    $this->conversions->recordOpportunityWon($opportunity->fresh(['lead', 'customer']));
                }
            }
        }

        return max(0, $this->conversionCountForLead($lead) - $before);
    }

    protected function countMissingConversions(Lead $lead): int
    {
        $missing = 0;

        if (! $this->hasConversion(MarketingConversion::LEAD_CREATED, leadId: $lead->id)) {
            $missing++;
        }

        $customer = Customer::withoutGlobalScopes()
            ->where('organization_id', $lead->organization_id)
            ->where('lead_id', $lead->id)
            ->first();

        $opportunity = Opportunity::withoutGlobalScopes()
            ->where('organization_id', $lead->organization_id)
            ->where('lead_id', $lead->id)
            ->orderBy('id')
            ->first();

        if ($customer) {
            if (! $this->hasConversion(MarketingConversion::LEAD_CONVERTED, leadId: $lead->id)) {
                $missing++;
            }
            if (! $this->hasConversion(MarketingConversion::CUSTOMER_CREATED, customerId: $customer->id)) {
                $missing++;
            }
            if ($opportunity) {
                if (! $this->hasConversion(MarketingConversion::OPPORTUNITY_CREATED, opportunityId: $opportunity->id)) {
                    $missing++;
                }
                if ($opportunity->isWon() && ! $this->hasConversion(MarketingConversion::OPPORTUNITY_WON, opportunityId: $opportunity->id)) {
                    $missing++;
                }
            }
        }

        return $missing;
    }

    protected function hasConversion(
        string $eventName,
        ?int $leadId = null,
        ?int $customerId = null,
        ?int $opportunityId = null,
    ): bool {
        $query = MarketingConversion::withoutGlobalScopes()->where('event_name', $eventName);

        if ($leadId !== null) {
            $query->where('lead_id', $leadId);
        }

        if ($customerId !== null) {
            $query->where('customer_id', $customerId);
        }

        if ($opportunityId !== null) {
            $query->where('opportunity_id', $opportunityId);
        }

        return $query->exists();
    }

    protected function conversionCountForLead(Lead $lead): int
    {
        return MarketingConversion::withoutGlobalScopes()
            ->where('lead_id', $lead->id)
            ->count();
    }

    protected function normalizeUuid(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || ! Str::isUuid($value)) {
            return null;
        }

        return $value;
    }

    /**
     * @return array{
     *     processed: int,
     *     skipped: int,
     *     attributed: int,
     *     conversions_replayed: int,
     *     failed: int,
     *     dry_run: bool,
     *     would_attribute: int,
     *     would_replay: int,
     * }
     */
    protected function emptyStats(bool $dryRun): array
    {
        return [
            'processed' => 0,
            'skipped' => 0,
            'attributed' => 0,
            'conversions_replayed' => 0,
            'failed' => 0,
            'dry_run' => $dryRun,
            'would_attribute' => 0,
            'would_replay' => 0,
        ];
    }

    protected function cursor(int $organizationId, string $scope): int
    {
        return (int) Cache::get($this->cursorKey($organizationId, $scope), 0);
    }

    protected function storeCursor(int $organizationId, string $scope, int $afterId): void
    {
        Cache::put(
            $this->cursorKey($organizationId, $scope),
            $afterId,
            (int) config('marketing.backfill.cursor_ttl_seconds', 604800),
        );
    }

    protected function resetCursor(int $organizationId, string $scope): void
    {
        Cache::forget($this->cursorKey($organizationId, $scope));
    }

    protected function cursorKey(int $organizationId, string $scope): string
    {
        return "marketing:backfill:cursor:{$organizationId}:{$scope}";
    }
}
