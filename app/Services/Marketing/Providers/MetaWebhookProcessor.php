<?php

namespace App\Services\Marketing\Providers;

use App\Models\MarketingProviderWebhookEvent;
use App\Services\MarketingProviderService;
use Throwable;

/**
 * Processes stored Meta webhook events (P7C.7).
 *
 * A webhook is a notification, not lead data. This processor selects stored
 * events, isolates per-event failures, and delegates all persistence and lead
 * creation to MarketingProviderService — the single write authority. It never
 * writes CRM data or creates leads directly, and it reuses the same import
 * pipeline as manual import (no second lead-creation path).
 */
class MetaWebhookProcessor
{
    public function __construct(
        protected MarketingProviderService $providers,
    ) {}

    /**
     * Process pending (received) webhook events for a provider slug.
     * Continues on partial failures; one failed event never blocks others.
     *
     * @return array{
     *     events: int,
     *     processed: int,
     *     failed: int,
     *     ignored: int,
     *     imported: int,
     *     skipped: int,
     *     lead_failed: int
     * }
     */
    public function processPending(string $slug = 'meta', ?int $limit = null): array
    {
        $query = MarketingProviderWebhookEvent::query()
            ->where('provider', $slug)
            ->where('event_type', '!=', MarketingProviderWebhookEvent::EVENT_VERIFICATION)
            ->where('processing_status', MarketingProviderWebhookEvent::STATUS_RECEIVED)
            ->orderBy('received_at')
            ->orderBy('id');

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        $events = $query->get();

        $summary = [
            'events' => 0,
            'processed' => 0,
            'failed' => 0,
            'ignored' => 0,
            'imported' => 0,
            'skipped' => 0,
            'lead_failed' => 0,
        ];

        foreach ($events as $event) {
            $summary['events']++;

            $result = $this->process($event);

            $summary['imported'] += $result['imported'] ?? 0;
            $summary['skipped'] += $result['skipped'] ?? 0;
            $summary['lead_failed'] += $result['failed'] ?? 0;

            match ($result['status'] ?? MarketingProviderWebhookEvent::STATUS_FAILED) {
                MarketingProviderWebhookEvent::STATUS_PROCESSED => $summary['processed']++,
                MarketingProviderWebhookEvent::STATUS_IGNORED => $summary['ignored']++,
                default => $summary['failed']++,
            };
        }

        return $summary;
    }

    /**
     * Process one event. Structural failures are recorded, never thrown, so
     * batch processing is resilient.
     *
     * @return array{ok: bool, status: string, imported: int, skipped: int, failed: int, organization_id: int|null, message: string|null}
     */
    public function process(MarketingProviderWebhookEvent $event): array
    {
        try {
            return $this->providers->processWebhookEvent($event);
        } catch (Throwable $e) {
            // Defense in depth: an unexpected error on one event must not abort
            // the batch. Record the failure on the event and continue.
            $event->forceFill([
                'processing_status' => MarketingProviderWebhookEvent::STATUS_FAILED,
                'processed_at' => now(),
                'failure_reason' => $e->getMessage(),
            ])->save();

            return [
                'ok' => false,
                'status' => MarketingProviderWebhookEvent::STATUS_FAILED,
                'imported' => 0,
                'skipped' => 0,
                'failed' => 1,
                'organization_id' => $event->organization_id,
                'message' => $e->getMessage(),
            ];
        }
    }
}
