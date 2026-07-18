<?php

namespace App\Services;

use App\Contracts\MarketingProviderAssetDiscoveryInterface;
use App\Contracts\MarketingProviderInterface;
use App\Contracts\MarketingProviderLeadFormSyncInterface;
use App\Contracts\MarketingProviderLeadImportInterface;
use App\Contracts\MarketingProviderLeadRetrievalInterface;
use App\Contracts\MarketingProviderSynchronizationInterface;
use App\Contracts\MarketingProviderWebhookInterface;
use App\Models\MarketingConversion;
use App\Models\MarketingProvider;
use App\Models\MarketingProviderCredential;
use App\Models\MarketingProviderImportedLead;
use App\Models\MarketingProviderLeadForm;
use App\Models\MarketingProviderLeadImportRun;
use App\Models\MarketingProviderSyncRun;
use App\Models\MarketingProviderUploadedConversion;
use App\Models\MarketingProviderWebhookEvent;
use App\Models\MarketingTouch;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use App\Services\Marketing\Providers\MarketingProviderRegistry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Throwable;

/**
 * Single write authority for marketing provider connections and credentials (P7C.1).
 *
 * Orchestrates adapters from MarketingProviderRegistry. Never bypasses the
 * Marketing Platform for visitors, touches, attributions, or conversions.
 */
class MarketingProviderService
{
    public function __construct(
        protected MarketingProviderRegistry $registry,
        protected LeadService $leads,
    ) {}

    /**
     * Ensure a tenant provider connection row exists (disconnected until credentials).
     */
    public function registerProvider(
        Organization $organization,
        string $slug,
        array $attributes = [],
    ): MarketingProvider {
        $existing = $this->findProvider($organization, $slug);

        if ($existing) {
            return $existing;
        }

        $displayName = $attributes['display_name']
            ?? $this->catalogName($slug)
            ?? ($this->registry->has($slug) ? $this->registry->resolve($slug)->displayName() : $slug);

        $capabilities = $attributes['capabilities']
            ?? ($this->registry->has($slug) ? $this->registry->resolve($slug)->capabilities() : []);

        return MarketingProvider::query()->create([
            'organization_id' => $organization->id,
            'slug' => $slug,
            'display_name' => $displayName,
            'status' => MarketingProvider::STATUS_DISCONNECTED,
            'external_account_id' => $attributes['external_account_id'] ?? null,
            'capabilities' => $capabilities,
            'metadata' => $attributes['metadata'] ?? null,
        ]);
    }

    public function findProvider(Organization $organization, string $slug): ?MarketingProvider
    {
        return MarketingProvider::query()
            ->where('organization_id', $organization->id)
            ->where('slug', $slug)
            ->first();
    }

    public function findProviderForOrganization(Organization $organization, int $providerId): ?MarketingProvider
    {
        return MarketingProvider::query()
            ->where('organization_id', $organization->id)
            ->whereKey($providerId)
            ->first();
    }

    /**
     * @return Collection<int, MarketingProvider>
     */
    public function listProviders(Organization $organization): Collection
    {
        return MarketingProvider::query()
            ->where('organization_id', $organization->id)
            ->orderBy('slug')
            ->get();
    }

    /**
     * Persist (or replace) encrypted credentials and transition health accordingly.
     *
     * @param  array{
     *     access_token?: string|null,
     *     refresh_token?: string|null,
     *     token_type?: string|null,
     *     scopes?: list<string>|null,
     *     expires_at?: \DateTimeInterface|string|null,
     *     metadata?: array<string, mixed>|null,
     *     configuration?: array<string, mixed>|null,
     *     external_account_id?: string|null,
     * }  $credentials
     */
    public function storeCredentials(MarketingProvider $provider, array $credentials): MarketingProviderCredential
    {
        return DB::transaction(function () use ($provider, $credentials) {
            $payload = [
                'organization_id' => $provider->organization_id,
                'marketing_provider_id' => $provider->id,
                'access_token' => $credentials['access_token'] ?? null,
                'refresh_token' => $credentials['refresh_token'] ?? null,
                'token_type' => $credentials['token_type'] ?? null,
                'scopes' => $credentials['scopes'] ?? null,
                'expires_at' => $credentials['expires_at'] ?? null,
                'metadata' => $credentials['metadata'] ?? null,
            ];

            if (array_key_exists('configuration', $credentials)) {
                $payload['configuration'] = $credentials['configuration'];
            }

            $credential = MarketingProviderCredential::query()->updateOrCreate(
                ['marketing_provider_id' => $provider->id],
                $payload,
            );

            if (array_key_exists('external_account_id', $credentials)) {
                $provider->external_account_id = $credentials['external_account_id'];
            }

            $credential->refresh();

            if ($credential->isExpired()) {
                $this->transitionStatus($provider, MarketingProvider::STATUS_EXPIRED, 'Credentials expired');
            } else {
                $this->transitionStatus($provider, MarketingProvider::STATUS_CONNECTED);
            }

            return $credential->fresh();
        });
    }

    public function clearCredentials(MarketingProvider $provider): void
    {
        DB::transaction(function () use ($provider) {
            MarketingProviderCredential::query()
                ->where('marketing_provider_id', $provider->id)
                ->delete();

            $this->transitionStatus($provider, MarketingProvider::STATUS_DISCONNECTED);
        });
    }

    public function disconnect(MarketingProvider $provider): MarketingProvider
    {
        if ($this->registry->has($provider->slug)) {
            try {
                $this->registry->resolve($provider->slug)->revoke($provider);
            } catch (Throwable) {
                // Best-effort remote revoke; local disconnect always proceeds.
            }
        }

        $this->clearCredentials($provider);

        return $provider->fresh();
    }

    /**
     * Canonical health state transition.
     */
    public function transitionStatus(
        MarketingProvider $provider,
        string $status,
        ?string $errorMessage = null,
    ): MarketingProvider {
        MarketingProvider::assertValidStatus($status);

        $attributes = [
            'status' => $status,
            'last_error' => $status === MarketingProvider::STATUS_ERROR || $status === MarketingProvider::STATUS_EXPIRED
                ? $errorMessage
                : null,
        ];

        if ($status === MarketingProvider::STATUS_CONNECTED) {
            $attributes['connected_at'] = $provider->connected_at ?? now();
            $attributes['disconnected_at'] = null;
            $attributes['last_error'] = null;
        }

        if ($status === MarketingProvider::STATUS_DISCONNECTED) {
            $attributes['connected_at'] = null;
            $attributes['disconnected_at'] = now();
            $attributes['last_error'] = null;
            $attributes['external_account_id'] = null;
        }

        $provider->fill($attributes);
        $provider->save();

        return $provider->fresh();
    }

    public function markConnected(MarketingProvider $provider): MarketingProvider
    {
        return $this->transitionStatus($provider, MarketingProvider::STATUS_CONNECTED);
    }

    public function markDisconnected(MarketingProvider $provider): MarketingProvider
    {
        return $this->transitionStatus($provider, MarketingProvider::STATUS_DISCONNECTED);
    }

    public function markExpired(MarketingProvider $provider, string $message = 'Credentials expired'): MarketingProvider
    {
        return $this->transitionStatus($provider, MarketingProvider::STATUS_EXPIRED, $message);
    }

    public function markError(MarketingProvider $provider, string $message): MarketingProvider
    {
        return $this->transitionStatus($provider, MarketingProvider::STATUS_ERROR, $message);
    }

    /**
     * Ask the adapter for health and persist the resulting status.
     *
     * @return array{healthy: bool, status: string, message: string|null, checked_at: string, metadata?: array<string, mixed>}
     */
    public function checkHealth(MarketingProvider $provider): array
    {
        $adapter = $this->requireAdapter($provider);
        $report = $adapter->reportHealth($provider);

        $provider->last_health_at = now();
        $provider->save();

        if (! ($report['healthy'] ?? false)) {
            $message = $report['message'] ?? 'Provider health check failed';
            $suggested = $report['status'] ?? MarketingProvider::STATUS_ERROR;

            if ($suggested === MarketingProvider::STATUS_EXPIRED) {
                $this->markExpired($provider, $message);
            } elseif ($suggested === MarketingProvider::STATUS_DISCONNECTED) {
                $this->markDisconnected($provider);
            } else {
                $this->markError($provider, $message);
            }

            return [
                'healthy' => false,
                'status' => $provider->fresh()->status,
                'message' => $message,
                'checked_at' => $provider->fresh()->last_health_at?->toIso8601String(),
                'metadata' => $report['metadata'] ?? [],
            ];
        }

        if ($this->credentialsAreExpired($provider)) {
            $this->markExpired($provider);

            return [
                'healthy' => false,
                'status' => MarketingProvider::STATUS_EXPIRED,
                'message' => 'Credentials expired',
                'checked_at' => $provider->fresh()->last_health_at?->toIso8601String(),
                'metadata' => $report['metadata'] ?? [],
            ];
        }

        $this->markConnected($provider);

        return [
            'healthy' => true,
            'status' => MarketingProvider::STATUS_CONNECTED,
            'message' => $report['message'] ?? null,
            'checked_at' => $provider->fresh()->last_health_at?->toIso8601String(),
            'metadata' => $report['metadata'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function authorize(MarketingProvider $provider, array $context = []): array
    {
        $adapter = $this->requireAdapter($provider);
        $result = $adapter->authorize($provider, $context);

        if (! empty($result['credentials']) && is_array($result['credentials'])) {
            $this->storeCredentials($provider, $result['credentials']);
        } elseif (! empty($result['status'])) {
            $this->transitionStatus($provider, (string) $result['status'], $result['message'] ?? null);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function refreshCredentials(MarketingProvider $provider): array
    {
        $adapter = $this->requireAdapter($provider);
        $result = $adapter->refreshCredentials($provider);

        if ($result !== []) {
            $this->storeCredentials($provider, $result);
        }

        return $result;
    }

    /**
     * Create a running synchronization history record.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function startSynchronization(
        MarketingProvider $provider,
        string $syncType,
        string $direction,
        array $metadata = [],
    ): MarketingProviderSyncRun {
        MarketingProviderSyncRun::assertValidSyncType($syncType);
        MarketingProviderSyncRun::assertValidDirection($direction);

        return MarketingProviderSyncRun::query()->create([
            'organization_id' => $provider->organization_id,
            'marketing_provider_id' => $provider->id,
            'sync_type' => $syncType,
            'direction' => $direction,
            'status' => MarketingProviderSyncRun::STATUS_RUNNING,
            'started_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Persist monotonic synchronization totals while a run is active.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function updateSynchronizationProgress(
        MarketingProviderSyncRun $run,
        int $processed,
        int $succeeded,
        int $failed,
        ?string $message = null,
        array $metadata = [],
    ): MarketingProviderSyncRun {
        if ($run->isFinished()) {
            throw new LogicException('Finished synchronization runs cannot be updated.');
        }

        if ($processed < 0 || $succeeded < 0 || $failed < 0 || $processed < ($succeeded + $failed)) {
            throw new InvalidArgumentException('Synchronization totals must be non-negative and processed must cover succeeded plus failed.');
        }

        if ($processed < $run->records_processed
            || $succeeded < $run->records_succeeded
            || $failed < $run->records_failed) {
            throw new InvalidArgumentException('Synchronization progress totals cannot decrease.');
        }

        $run->fill([
            'records_processed' => $processed,
            'records_succeeded' => $succeeded,
            'records_failed' => $failed,
            'message' => $message ?? $run->message,
            'metadata' => array_replace_recursive($run->metadata ?? [], $metadata),
        ]);
        $run->save();

        return $run->fresh();
    }

    /**
     * Finalize an active run. Historical rows are retained permanently.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function finishSynchronization(
        MarketingProviderSyncRun $run,
        string $status,
        ?string $message = null,
        array $metadata = [],
    ): MarketingProviderSyncRun {
        MarketingProviderSyncRun::assertValidStatus($status);

        if (! in_array($status, MarketingProviderSyncRun::TERMINAL_STATUSES, true)) {
            throw new InvalidArgumentException("Synchronization cannot finish with non-terminal status [{$status}].");
        }

        if ($run->isFinished()) {
            throw new LogicException('Synchronization run is already finished.');
        }

        $run->fill([
            'status' => $status,
            'finished_at' => now(),
            'message' => $message ?? $run->message,
            'metadata' => array_replace_recursive($run->metadata ?? [], $metadata),
        ]);
        $run->save();

        return $run->fresh();
    }

    /**
     * Record an execution failure while preserving all progress already written.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function recordSynchronizationFailure(
        MarketingProviderSyncRun $run,
        Throwable|string $failure,
        array $metadata = [],
    ): MarketingProviderSyncRun {
        $message = $failure instanceof Throwable ? $failure->getMessage() : $failure;

        if ($failure instanceof Throwable) {
            $metadata['exception'] = $failure::class;
        }

        $status = $run->records_succeeded > 0
            ? MarketingProviderSyncRun::STATUS_PARTIAL
            : MarketingProviderSyncRun::STATUS_FAILED;

        return $this->finishSynchronization($run, $status, $message, $metadata);
    }

    public function cancelSynchronization(
        MarketingProviderSyncRun $run,
        ?string $message = null,
    ): MarketingProviderSyncRun {
        return $this->finishSynchronization(
            $run,
            MarketingProviderSyncRun::STATUS_CANCELLED,
            $message ?? 'Synchronization cancelled.',
        );
    }

    /**
     * @return Collection<int, MarketingProviderSyncRun>
     */
    public function synchronizationHistory(MarketingProvider $provider, int $limit = 50): Collection
    {
        $limit = max(1, min($limit, 100));

        return MarketingProviderSyncRun::query()
            ->where('organization_id', $provider->organization_id)
            ->where('marketing_provider_id', $provider->id)
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function supportsSynchronization(MarketingProvider $provider): bool
    {
        return $this->registry->has($provider->slug)
            && $this->registry->resolve($provider->slug) instanceof MarketingProviderSynchronizationInterface;
    }

    /**
     * Execute provider work inside the reusable synchronization lifecycle.
     *
     * Runtime options:
     * - sync_type: one of MarketingProviderSyncRun::SYNC_TYPES
     * - direction: inbound or outbound
     * - metadata: history metadata recorded at start
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function synchronize(MarketingProvider $provider, array $options = []): array
    {
        $adapter = $this->requireSynchronizationAdapter($provider);

        if (! $provider->isConnected() && $provider->status !== MarketingProvider::STATUS_EXPIRED) {
            throw new InvalidArgumentException('Provider must be connected (or expired pending refresh) before synchronize.');
        }

        $syncType = (string) ($options['sync_type'] ?? MarketingProviderSyncRun::TYPE_ASSET_DISCOVERY);
        $direction = (string) ($options['direction'] ?? (
            $syncType === MarketingProviderSyncRun::TYPE_CONVERSION_UPLOAD
                ? MarketingProviderSyncRun::DIRECTION_OUTBOUND
                : MarketingProviderSyncRun::DIRECTION_INBOUND
        ));
        $metadata = is_array($options['metadata'] ?? null) ? $options['metadata'] : [];

        $run = $this->startSynchronization($provider, $syncType, $direction, $metadata);

        try {
            $result = $adapter->synchronize($provider, $options);
            $totals = $this->synchronizationTotalsFromResult($result);
            $run = $this->updateSynchronizationProgress(
                $run,
                $totals['processed'],
                $totals['succeeded'],
                $totals['failed'],
                $result['message'] ?? null,
                is_array($result['metadata'] ?? null) ? $result['metadata'] : [],
            );

            $status = $this->synchronizationStatusFromResult($result, $totals);
            $run = $this->finishSynchronization($run, $status, $result['message'] ?? null);
        } catch (Throwable $e) {
            $run = $this->recordSynchronizationFailure($run, $e);
            $this->markError($provider, $e->getMessage());

            throw $e;
        }

        if (in_array($run->status, [
            MarketingProviderSyncRun::STATUS_COMPLETED,
            MarketingProviderSyncRun::STATUS_PARTIAL,
        ], true)) {
            $provider->last_synced_at = $run->finished_at;

            if ($provider->status !== MarketingProvider::STATUS_CONNECTED) {
                $this->markConnected($provider);
            } else {
                $provider->save();
            }
        } elseif ($run->status === MarketingProviderSyncRun::STATUS_FAILED) {
            $this->markError($provider, $run->message ?? 'Synchronization failed');
        }

        return array_merge($result, [
            'sync_run' => $run,
            'sync_run_id' => $run->id,
            'sync_status' => $run->status,
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array{processed: int, succeeded: int, failed: int}
     */
    protected function synchronizationTotalsFromResult(array $result): array
    {
        $accounts = is_array($result['accounts'] ?? null) ? $result['accounts'] : [];
        $campaigns = is_array($result['campaigns'] ?? null) ? $result['campaigns'] : [];
        $inferred = count($accounts) + count($campaigns);
        $failed = max(0, (int) ($result['records_failed'] ?? $result['failed'] ?? 0));
        $succeeded = max(0, (int) ($result['records_succeeded'] ?? $result['succeeded'] ?? (
            ($result['ok'] ?? false) ? $inferred : 0
        )));
        $processed = max(
            0,
            (int) ($result['records_processed'] ?? $result['processed'] ?? $inferred),
            $succeeded + $failed,
        );

        if (! ($result['ok'] ?? false) && $failed === 0) {
            $failed = max(1, $processed - $succeeded);
            $processed = max($processed, $succeeded + $failed);
        }

        return compact('processed', 'succeeded', 'failed');
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array{processed: int, succeeded: int, failed: int}  $totals
     */
    protected function synchronizationStatusFromResult(array $result, array $totals): string
    {
        if ($totals['failed'] > 0) {
            return $totals['succeeded'] > 0
                ? MarketingProviderSyncRun::STATUS_PARTIAL
                : MarketingProviderSyncRun::STATUS_FAILED;
        }

        return ($result['ok'] ?? false)
            ? MarketingProviderSyncRun::STATUS_COMPLETED
            : MarketingProviderSyncRun::STATUS_FAILED;
    }

    /**
     * Verify an inbound webhook subscription challenge (e.g. Meta hub.challenge).
     * Persists a verification event on success. Does not create CRM data.
     *
     * @param  array<string, mixed>  $query
     * @return array{ok: bool, challenge?: string|null, message?: string|null, event?: MarketingProviderWebhookEvent|null}
     */
    public function verifyWebhook(string $slug, array $query): array
    {
        $adapter = $this->requireWebhookAdapter($slug);
        $result = $adapter->verifyWebhook($query);

        if ($result['ok'] ?? false) {
            $event = $this->storeWebhookEvent([
                'organization_id' => null,
                'provider' => $slug,
                'event_type' => MarketingProviderWebhookEvent::EVENT_VERIFICATION,
                'delivery_id' => 'verify-'.hash('sha256', $slug.'|'.($result['challenge'] ?? '').'|'.microtime(true)),
                'payload' => [
                    'hub.mode' => $query['hub_mode'] ?? $query['hub.mode'] ?? null,
                ],
                'signature' => null,
                'received_at' => now(),
                'processed_at' => now(),
                'processing_status' => MarketingProviderWebhookEvent::STATUS_VERIFIED,
            ]);

            $result['event'] = $event;
        }

        return $result;
    }

    /**
     * Ingest a public webhook delivery for a provider slug.
     * Validates via the adapter, persists the raw event, and does not process it.
     * Organization resolution is deferred — events are stored with organization_id = null.
     *
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    public function ingestWebhook(string $slug, string $rawBody, array $headers = []): array
    {
        if (! $this->registry->has($slug)) {
            return [
                'ok' => false,
                'message' => "Unknown marketing provider [{$slug}].",
                'http_status' => 404,
            ];
        }

        $adapter = $this->requireWebhookAdapter($slug);

        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) {
            return [
                'ok' => false,
                'message' => 'Malformed webhook payload.',
                'http_status' => 400,
            ];
        }

        $result = $adapter->validateAndNormalizeWebhook($rawBody, $payload, $headers);

        if (! ($result['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => $result['message'] ?? 'Webhook rejected.',
                'http_status' => $result['http_status'] ?? 401,
                'event' => $result['event'] ?? null,
            ];
        }

        return $this->persistValidatedWebhook(
            slug: $slug,
            organizationId: null,
            payload: $payload,
            result: $result,
        );
    }

    /**
     * Validate and normalize via the adapter, then persist the raw event.
     * Does not create CRM leads or process business events.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    public function receiveWebhook(MarketingProvider $provider, array $payload, array $headers = []): array
    {
        $adapter = $this->requireAdapter($provider);
        $result = $adapter->receiveWebhook($provider, $payload, $headers);

        if (! ($result['ok'] ?? false)) {
            return $result;
        }

        $persisted = $this->persistValidatedWebhook(
            slug: $provider->slug,
            organizationId: $provider->organization_id,
            payload: $payload,
            result: $result,
        );

        return array_merge($result, [
            'ok' => $persisted['ok'],
            'duplicate' => $persisted['duplicate'] ?? false,
            'webhook_event' => $persisted['webhook_event'] ?? null,
            'webhook_event_id' => $persisted['webhook_event_id'] ?? null,
            'processing_status' => $persisted['processing_status'] ?? null,
            'message' => $persisted['message'] ?? $result['message'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function persistValidatedWebhook(
        string $slug,
        ?int $organizationId,
        array $payload,
        array $result,
    ): array {
        $deliveryId = is_string($result['delivery_id'] ?? null) && $result['delivery_id'] !== ''
            ? $result['delivery_id']
            : hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        $existing = MarketingProviderWebhookEvent::query()
            ->where('provider', $slug)
            ->where('delivery_id', $deliveryId)
            ->first();

        if ($existing) {
            return [
                'ok' => true,
                'duplicate' => true,
                'webhook_event' => $existing,
                'webhook_event_id' => $existing->id,
                'processing_status' => $existing->processing_status,
                'event' => $result['event'] ?? $existing->event_type,
                'normalized' => $result['normalized'] ?? $existing->payload,
                'message' => 'Duplicate webhook delivery ignored.',
                'http_status' => 200,
            ];
        }

        $storePayload = is_array($result['normalized'] ?? null)
            ? [
                'raw' => $payload,
                'normalized' => $result['normalized'],
            ]
            : $payload;

        $event = $this->storeWebhookEvent([
            'organization_id' => $organizationId,
            'provider' => $slug,
            'event_type' => $result['event'] ?? 'unknown',
            'delivery_id' => $deliveryId,
            'payload' => $storePayload,
            'signature' => is_string($result['signature'] ?? null) ? $result['signature'] : null,
            'received_at' => now(),
            'processed_at' => null,
            'processing_status' => MarketingProviderWebhookEvent::STATUS_RECEIVED,
        ]);

        return [
            'ok' => true,
            'duplicate' => false,
            'webhook_event' => $event,
            'webhook_event_id' => $event->id,
            'processing_status' => $event->processing_status,
            'event' => $event->event_type,
            'normalized' => $result['normalized'] ?? null,
            'message' => null,
            'http_status' => 200,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function storeWebhookEvent(array $attributes): MarketingProviderWebhookEvent
    {
        MarketingProviderWebhookEvent::assertValidStatus(
            (string) ($attributes['processing_status'] ?? MarketingProviderWebhookEvent::STATUS_RECEIVED)
        );

        return MarketingProviderWebhookEvent::query()->create($attributes);
    }

    /**
     * Application-level webhook status for Integration UI (no event processing).
     *
     * @return array{
     *     supported: bool,
     *     status: string,
     *     last_received_at: string|null,
     *     last_verified_at: string|null,
     *     last_event_type: string|null,
     *     last_processing_status: string|null
     * }
     */
    public function webhookStatus(string $slug): array
    {
        $supported = $this->registry->has($slug)
            && $this->registry->resolve($slug) instanceof MarketingProviderWebhookInterface;

        if (! $supported) {
            return [
                'supported' => false,
                'status' => 'unsupported',
                'last_received_at' => null,
                'last_verified_at' => null,
                'last_event_type' => null,
                'last_processing_status' => null,
            ];
        }

        $lastReceived = MarketingProviderWebhookEvent::query()
            ->where('provider', $slug)
            ->where('event_type', '!=', MarketingProviderWebhookEvent::EVENT_VERIFICATION)
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->first();

        $lastVerified = MarketingProviderWebhookEvent::query()
            ->where('provider', $slug)
            ->where('event_type', MarketingProviderWebhookEvent::EVENT_VERIFICATION)
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->first();

        $status = 'awaiting';
        if ($lastReceived) {
            $status = 'receiving';
        } elseif ($lastVerified) {
            $status = 'verified';
        }

        $lastProcessed = MarketingProviderWebhookEvent::query()
            ->where('provider', $slug)
            ->where('event_type', '!=', MarketingProviderWebhookEvent::EVENT_VERIFICATION)
            ->whereNotNull('processed_at')
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->first();

        $processedCount = MarketingProviderWebhookEvent::query()
            ->where('provider', $slug)
            ->where('processing_status', MarketingProviderWebhookEvent::STATUS_PROCESSED)
            ->count();

        $failedCount = MarketingProviderWebhookEvent::query()
            ->where('provider', $slug)
            ->where('processing_status', MarketingProviderWebhookEvent::STATUS_FAILED)
            ->count();

        $pendingCount = MarketingProviderWebhookEvent::query()
            ->where('provider', $slug)
            ->where('event_type', '!=', MarketingProviderWebhookEvent::EVENT_VERIFICATION)
            ->where('processing_status', MarketingProviderWebhookEvent::STATUS_RECEIVED)
            ->count();

        return [
            'supported' => true,
            'status' => $status,
            'last_received_at' => $lastReceived?->received_at?->toIso8601String(),
            'last_verified_at' => $lastVerified?->received_at?->toIso8601String(),
            'last_event_type' => $lastReceived?->event_type,
            'last_processing_status' => $lastReceived?->processing_status,
            'last_processed_at' => $lastProcessed?->processed_at?->toIso8601String(),
            'last_processing_result' => $lastProcessed?->processing_status,
            'last_failure_reason' => $lastProcessed?->failure_reason,
            'processed_count' => $processedCount,
            'failed_count' => $failedCount,
            'pending_count' => $pendingCount,
        ];
    }

    public function supportsWebhooks(MarketingProvider $provider): bool
    {
        if (! $this->registry->has($provider->slug)) {
            return false;
        }

        return $this->registry->resolve($provider->slug) instanceof MarketingProviderWebhookInterface;
    }

    /**
     * Resolve which tenant provider connection owns an inbound webhook, using
     * only stored credential configuration (page_id / lead_form_ids). Request
     * parameters are never trusted; tenants are never inferred. Form matches are
     * authoritative; page matches are a fallback.
     *
     * @return array{provider: MarketingProvider|null, reason: string|null}
     */
    public function resolveWebhookProvider(string $slug, ?string $pageId, ?string $formId): array
    {
        $candidates = MarketingProvider::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('slug', $slug)
            ->with(['credential' => fn ($q) => $q->withoutGlobalScope(OrganizationScope::class)])
            ->get()
            ->filter(fn (MarketingProvider $provider) => $provider->credential !== null);

        $formId = $formId !== null && $formId !== '' ? $formId : null;
        $pageId = $pageId !== null && $pageId !== '' ? $pageId : null;

        if ($formId !== null) {
            $formMatches = $candidates->filter(function (MarketingProvider $provider) use ($formId) {
                $ids = $provider->credential->configuration['lead_form_ids'] ?? [];

                return is_array($ids) && in_array($formId, array_map('strval', $ids), true);
            })->values();

            if ($formMatches->count() === 1) {
                return ['provider' => $formMatches->first(), 'reason' => null];
            }

            if ($formMatches->count() > 1) {
                return ['provider' => null, 'reason' => 'ambiguous_organization'];
            }
        }

        if ($pageId !== null) {
            $pageMatches = $candidates->filter(function (MarketingProvider $provider) use ($pageId) {
                return (string) ($provider->credential->configuration['page_id'] ?? '') === $pageId;
            })->values();

            if ($pageMatches->count() === 1) {
                return ['provider' => $pageMatches->first(), 'reason' => null];
            }

            if ($pageMatches->count() > 1) {
                return ['provider' => null, 'reason' => 'ambiguous_organization'];
            }
        }

        return ['provider' => null, 'reason' => 'no_organization'];
    }

    /**
     * Process a single stored webhook event: resolve tenant, retrieve complete
     * lead details via the adapter, and create leads through the shared import
     * pipeline. Idempotent and self-contained — one failing event never affects
     * others. This is the single write authority for webhook lifecycle changes.
     *
     * @return array{ok: bool, status: string, imported: int, skipped: int, failed: int, organization_id: int|null, message: string|null}
     */
    public function processWebhookEvent(MarketingProviderWebhookEvent $event): array
    {
        if (in_array($event->processing_status, [
            MarketingProviderWebhookEvent::STATUS_PROCESSED,
            MarketingProviderWebhookEvent::STATUS_IGNORED,
        ], true)) {
            return [
                'ok' => true,
                'status' => $event->processing_status,
                'imported' => 0,
                'skipped' => 0,
                'failed' => 0,
                'organization_id' => $event->organization_id,
                'message' => 'Event already finalized.',
            ];
        }

        $this->transitionWebhookEvent($event, MarketingProviderWebhookEvent::STATUS_PROCESSING, [
            'processing_attempts' => $event->processing_attempts + 1,
        ]);

        if ($event->event_type === MarketingProviderWebhookEvent::EVENT_VERIFICATION) {
            $this->transitionWebhookEvent($event, MarketingProviderWebhookEvent::STATUS_IGNORED, [
                'processed_at' => now(),
                'failure_reason' => 'Verification events are not lead notifications.',
            ]);

            return $this->webhookOutcome($event, 0, 0, 0, null, 'Verification event ignored.');
        }

        $leadgen = $this->extractLeadgenRefs($event);

        if ($leadgen === []) {
            $this->transitionWebhookEvent($event, MarketingProviderWebhookEvent::STATUS_IGNORED, [
                'processed_at' => now(),
                'failure_reason' => 'No leadgen changes to process.',
            ]);

            return $this->webhookOutcome($event, 0, 0, 0, null, 'No leadgen changes to process.');
        }

        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];
        $resolvedOrganizationId = $event->organization_id;

        foreach ($leadgen as $ref) {
            $leadId = $this->stringOrNull($ref['leadgen_id'] ?? null);

            if ($leadId === null) {
                $failed++;
                $errors[] = 'Missing leadgen id.';

                continue;
            }

            $resolution = $this->resolveWebhookProvider(
                $event->provider,
                $this->stringOrNull($ref['page_id'] ?? null),
                $this->stringOrNull($ref['form_id'] ?? null),
            );

            $provider = $resolution['provider'];

            if ($provider === null) {
                $failed++;
                $errors[] = sprintf('Lead %s: %s.', $leadId, $resolution['reason'] ?? 'unresolved organization');

                continue;
            }

            $resolvedOrganizationId ??= $provider->organization_id;

            $adapter = $this->registry->resolve($event->provider);

            if (! $adapter instanceof MarketingProviderLeadRetrievalInterface) {
                $failed++;
                $errors[] = sprintf('Lead %s: provider does not support lead retrieval.', $leadId);

                continue;
            }

            $retrieval = $adapter->retrieveLeadEntry($provider, $leadId, [
                'form_id' => $ref['form_id'] ?? null,
                'page_id' => $ref['page_id'] ?? null,
            ]);

            $this->applyProviderStatusFromResult($provider, $retrieval);

            if (! ($retrieval['ok'] ?? false) || ! is_array($retrieval['entry'] ?? null)) {
                $failed++;
                $errors[] = sprintf('Lead %s: %s', $leadId, $retrieval['message'] ?? 'lead retrieval failed');

                continue;
            }

            $user = $provider->organization?->primaryOwner();

            if ($user === null) {
                $failed++;
                $errors[] = sprintf('Lead %s: organization has no user to attribute the lead to.', $leadId);

                continue;
            }

            $outcome = $this->importNormalizedEntry($provider, $retrieval['entry'], $user);

            match ($outcome['result']) {
                'imported' => $imported++,
                'skipped' => $skipped++,
                default => $failed++,
            };

            if ($outcome['result'] === 'failed' && $outcome['error'] !== null) {
                $errors[] = sprintf('Lead %s: %s', $leadId, $outcome['error']);
            }
        }

        $status = ($imported === 0 && $skipped === 0 && $failed > 0)
            ? MarketingProviderWebhookEvent::STATUS_FAILED
            : MarketingProviderWebhookEvent::STATUS_PROCESSED;

        $this->transitionWebhookEvent($event, $status, [
            'organization_id' => $resolvedOrganizationId,
            'processed_at' => now(),
            'failure_reason' => $errors === [] ? null : implode(' ', array_slice($errors, 0, 10)),
        ]);

        return $this->webhookOutcome(
            $event,
            $imported,
            $skipped,
            $failed,
            $resolvedOrganizationId,
            sprintf('Imported %d, skipped %d, failed %d.', $imported, $skipped, $failed),
        );
    }

    /**
     * @return array{ok: bool, status: string, imported: int, skipped: int, failed: int, organization_id: int|null, message: string|null}
     */
    protected function webhookOutcome(
        MarketingProviderWebhookEvent $event,
        int $imported,
        int $skipped,
        int $failed,
        ?int $organizationId,
        ?string $message,
    ): array {
        return [
            'ok' => $event->processing_status !== MarketingProviderWebhookEvent::STATUS_FAILED,
            'status' => $event->processing_status,
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
            'organization_id' => $organizationId,
            'message' => $message,
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function transitionWebhookEvent(
        MarketingProviderWebhookEvent $event,
        string $status,
        array $extra = [],
    ): void {
        MarketingProviderWebhookEvent::assertValidStatus($status);

        $event->fill(array_merge(['processing_status' => $status], $extra));
        $event->save();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function extractLeadgenRefs(MarketingProviderWebhookEvent $event): array
    {
        $payload = $event->payload ?? [];
        $normalized = is_array($payload['normalized'] ?? null) ? $payload['normalized'] : null;
        $leadgen = is_array($normalized['leadgen'] ?? null) ? $normalized['leadgen'] : [];

        return array_values(array_filter($leadgen, 'is_array'));
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function applyProviderStatusFromResult(MarketingProvider $provider, array $result): void
    {
        if ($result['ok'] ?? false) {
            return;
        }

        $suggested = $result['status'] ?? null;

        if ($suggested === MarketingProvider::STATUS_EXPIRED) {
            $this->markExpired($provider, $result['message'] ?? 'Credentials expired');
        } elseif ($suggested === MarketingProvider::STATUS_ERROR) {
            $this->markError($provider, $result['message'] ?? 'Provider error during webhook processing');
        }
    }

    protected function stringOrNull(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    /**
     * Upload CRM conversions through the Synchronization Runtime.
     * Adapters translate payloads; this service owns dedup + sync history.
     *
     * Pass an empty $conversions list to upload all pending org conversions
     * for this provider connection. Non-empty lists are treated as already
     * normalized DTOs (test / explicit replay callers).
     *
     * @param  list<array<string, mixed>>  $conversions
     * @return array<string, mixed>
     */
    public function uploadConversions(MarketingProvider $provider, array $conversions = []): array
    {
        $adapter = $this->requireAdapter($provider);

        if (! $provider->isConnected() && $provider->status !== MarketingProvider::STATUS_EXPIRED) {
            throw new InvalidArgumentException('Provider must be connected before uploading conversions.');
        }

        $skipped = 0;
        $prepared = $conversions;

        if ($conversions === []) {
            [$prepared, $skipped] = $this->preparePendingConversionUploads($provider);
        }

        $run = $this->startSynchronization(
            $provider,
            MarketingProviderSyncRun::TYPE_CONVERSION_UPLOAD,
            MarketingProviderSyncRun::DIRECTION_OUTBOUND,
            [
                'pending' => count($prepared),
                'skipped_duplicates' => $skipped,
            ],
        );

        $result = [];

        try {
            $result = $adapter->uploadConversions($provider, $prepared);
            $this->applyProviderStatusFromResult($provider, $result);

            $uploaded = 0;
            $failed = 0;
            $errors = [];
            $rows = is_array($result['results'] ?? null) ? $result['results'] : [];

            if ($rows !== []) {
                foreach ($rows as $row) {
                    if (! is_array($row)) {
                        $failed++;

                        continue;
                    }

                    if ($row['ok'] ?? false) {
                        $uploaded++;
                        $this->recordUploadedConversion($provider, $row);

                        continue;
                    }

                    $failed++;
                    if (isset($row['message'])) {
                        $errors[] = [
                            'conversion_id' => $row['conversion_id'] ?? null,
                            'error' => $row['message'],
                        ];
                    }
                }
            } else {
                $uploaded = max(0, (int) ($result['uploaded'] ?? 0));
                $failed = max(0, (int) ($result['failed'] ?? 0));
            }

            if ($prepared === [] && $skipped === 0 && ! ($result['ok'] ?? false) && $failed === 0) {
                $failed = 1;
            }

            $processed = $uploaded + $skipped + $failed;
            $message = $result['message']
                ?? sprintf('Uploaded %d, skipped %d, failed %d.', $uploaded, $skipped, $failed);

            $run = $this->updateSynchronizationProgress(
                $run,
                $processed,
                $uploaded,
                $failed,
                $message,
                [
                    'uploaded' => $uploaded,
                    'skipped' => $skipped,
                    'failed' => $failed,
                    'errors' => array_slice($errors, 0, 25),
                    'pixel_id' => is_array($result['metadata'] ?? null)
                        ? ($result['metadata']['pixel_id'] ?? null)
                        : null,
                    'customer_id' => is_array($result['metadata'] ?? null)
                        ? ($result['metadata']['customer_id'] ?? null)
                        : null,
                ],
            );

            $status = match (true) {
                $prepared === [] && $failed === 0 => MarketingProviderSyncRun::STATUS_COMPLETED,
                $failed > 0 && $uploaded > 0 => MarketingProviderSyncRun::STATUS_PARTIAL,
                $failed > 0 && $uploaded === 0 && $skipped === 0 => MarketingProviderSyncRun::STATUS_FAILED,
                $failed > 0 => MarketingProviderSyncRun::STATUS_PARTIAL,
                default => MarketingProviderSyncRun::STATUS_COMPLETED,
            };

            if ($prepared === [] && $failed === 0) {
                $message = $skipped > 0
                    ? sprintf('No new conversions to upload (%d already uploaded).', $skipped)
                    : 'No conversions pending upload.';
            }

            $run = $this->finishSynchronization($run, $status, $message);
        } catch (Throwable $e) {
            $run = $this->recordSynchronizationFailure($run, $e);
            $this->markError($provider, $e->getMessage());

            throw $e;
        }

        if (in_array($run->status, [
            MarketingProviderSyncRun::STATUS_COMPLETED,
            MarketingProviderSyncRun::STATUS_PARTIAL,
        ], true)) {
            $provider->last_synced_at = $run->finished_at;
            $provider->save();
        }

        return [
            'ok' => $run->status !== MarketingProviderSyncRun::STATUS_FAILED,
            'uploaded' => (int) ($run->metadata['uploaded'] ?? $run->records_succeeded),
            'skipped' => (int) ($run->metadata['skipped'] ?? 0),
            'failed' => (int) ($run->metadata['failed'] ?? $run->records_failed),
            'processed' => $run->records_processed,
            'status' => $run->status,
            'message' => $run->message,
            'sync_run' => $run,
            'sync_run_id' => $run->id,
            'results' => $result['results'] ?? [],
        ];
    }

    public function supportsOfflineConversions(MarketingProvider $provider): bool
    {
        if (! $this->registry->has($provider->slug)) {
            return false;
        }

        return in_array('offline_conversions', $this->registry->resolve($provider->slug)->capabilities(), true);
    }

    public function latestConversionUploadRun(MarketingProvider $provider): ?MarketingProviderSyncRun
    {
        return MarketingProviderSyncRun::query()
            ->where('organization_id', $provider->organization_id)
            ->where('marketing_provider_id', $provider->id)
            ->where('sync_type', MarketingProviderSyncRun::TYPE_CONVERSION_UPLOAD)
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: int}
     */
    protected function preparePendingConversionUploads(MarketingProvider $provider): array
    {
        $alreadyUploaded = MarketingProviderUploadedConversion::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $provider->organization_id)
            ->where('marketing_provider_id', $provider->id)
            ->pluck('marketing_conversion_id')
            ->all();

        $conversions = MarketingConversion::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $provider->organization_id)
            ->with(['lead', 'attribution.session'])
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        $skipped = 0;
        $prepared = [];

        foreach ($conversions as $conversion) {
            if (in_array($conversion->id, $alreadyUploaded, true)) {
                $skipped++;

                continue;
            }

            $dto = $this->mapConversionToUploadDto($conversion);

            if ($dto === null) {
                continue;
            }

            $prepared[] = $dto;
        }

        return [$prepared, $skipped];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function mapConversionToUploadDto(MarketingConversion $conversion): ?array
    {
        $lead = $conversion->lead;
        $email = is_string($lead?->email) ? trim($lead->email) : null;
        $phone = is_string($lead?->phone) ? trim($lead->phone) : null;
        $fbclid = $this->resolveFbclidForConversion($conversion);
        $gclid = $this->resolveGclidForConversion($conversion);
        $externalLeadId = null;

        if (is_array($lead?->custom_fields['provider'] ?? null)) {
            $externalLeadId = $this->stringOrNull($lead->custom_fields['provider']['external_lead_id'] ?? null);
        }

        if (($email === null || $email === '')
            && ($phone === null || $phone === '')
            && $fbclid === null
            && $gclid === null
            && $externalLeadId === null) {
            return null;
        }

        return [
            'conversion_id' => $conversion->id,
            'event_name' => $conversion->event_name,
            'event_time' => $conversion->occurred_at?->getTimestamp() ?? now()->getTimestamp(),
            'event_id' => 'nova_crm_conversion_'.$conversion->id,
            'event_value' => $conversion->event_value,
            'currency' => $conversion->currency,
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
            'fbclid' => $fbclid,
            'gclid' => $gclid,
            'external_lead_id' => $externalLeadId,
            'lead_id' => $conversion->lead_id,
            'organization_id' => $conversion->organization_id,
        ];
    }

    protected function resolveGclidForConversion(MarketingConversion $conversion): ?string
    {
        $attribution = $conversion->attribution;

        if ($attribution === null) {
            return null;
        }

        $sessionId = $attribution->marketing_session_id;
        $visitorId = $attribution->marketing_visitor_id;

        $query = MarketingTouch::query()->whereNotNull('gclid');

        if ($sessionId) {
            $gclid = (clone $query)
                ->where('session_id', $sessionId)
                ->orderBy('occurred_at')
                ->orderBy('id')
                ->value('gclid');

            if (is_string($gclid) && $gclid !== '') {
                return $gclid;
            }
        }

        if ($visitorId) {
            $gclid = MarketingTouch::query()
                ->whereNotNull('gclid')
                ->whereHas('session', fn ($q) => $q->where('visitor_id', $visitorId))
                ->orderBy('occurred_at')
                ->orderBy('id')
                ->value('gclid');

            if (is_string($gclid) && $gclid !== '') {
                return $gclid;
            }
        }

        return null;
    }

    protected function resolveFbclidForConversion(MarketingConversion $conversion): ?string
    {
        $attribution = $conversion->attribution;

        if ($attribution === null) {
            return null;
        }

        $sessionId = $attribution->marketing_session_id;
        $visitorId = $attribution->marketing_visitor_id;

        $query = MarketingTouch::query()->whereNotNull('fbclid');

        if ($sessionId) {
            $fbclid = (clone $query)
                ->where('session_id', $sessionId)
                ->orderBy('occurred_at')
                ->orderBy('id')
                ->value('fbclid');

            if (is_string($fbclid) && $fbclid !== '') {
                return $fbclid;
            }
        }

        if ($visitorId) {
            $fbclid = MarketingTouch::query()
                ->whereNotNull('fbclid')
                ->whereHas('session', fn ($q) => $q->where('visitor_id', $visitorId))
                ->orderBy('occurred_at')
                ->orderBy('id')
                ->value('fbclid');

            if (is_string($fbclid) && $fbclid !== '') {
                return $fbclid;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function recordUploadedConversion(MarketingProvider $provider, array $row): void
    {
        $conversionId = $row['conversion_id'] ?? null;

        if (! is_numeric($conversionId)) {
            return;
        }

        MarketingProviderUploadedConversion::query()->updateOrCreate(
            [
                'organization_id' => $provider->organization_id,
                'marketing_provider_id' => $provider->id,
                'marketing_conversion_id' => (int) $conversionId,
            ],
            [
                'external_event_id' => is_string($row['external_event_id'] ?? null)
                    ? $row['external_event_id']
                    : null,
                'provider_event_name' => is_string($row['provider_event_name'] ?? null)
                    ? $row['provider_event_name']
                    : null,
                'metadata' => [
                    'events_received' => $row['events_received'] ?? null,
                    'fbtrace_id' => $row['fbtrace_id'] ?? null,
                    'google_job_id' => $row['google_job_id'] ?? null,
                ],
                'uploaded_at' => now(),
            ],
        );
    }

    /**
     * Discover selectable assets for a connected provider (read-only).
     * Does not write Marketing Platform tables. On failure, updates status
     * without clearing existing credential configuration.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function discoverAssets(
        MarketingProvider $provider,
        array $options = [],
        bool $updateStatusOnFailure = true,
    ): array {
        $adapter = $this->requireAssetDiscoveryAdapter($provider);
        $result = $adapter->discoverAssets($provider, $options);

        if ($updateStatusOnFailure && ! ($result['ok'] ?? false)) {
            $message = $result['message'] ?? 'Asset discovery failed';
            $suggested = $result['status'] ?? MarketingProvider::STATUS_ERROR;

            if ($suggested === MarketingProvider::STATUS_EXPIRED) {
                $this->markExpired($provider, $message);
            } elseif ($suggested === MarketingProvider::STATUS_DISCONNECTED) {
                $this->markDisconnected($provider);
            } elseif ($suggested === MarketingProvider::STATUS_ERROR) {
                $this->markError($provider, $message);
            }
        }

        return $result;
    }

    /**
     * Validate and persist selected assets into credential.configuration only.
     * Never trusts client IDs without adapter verification against live discovery.
     *
     * @param  array<string, mixed>  $selection
     */
    public function saveAssetConfiguration(MarketingProvider $provider, array $selection): MarketingProviderCredential
    {
        $adapter = $this->requireAssetDiscoveryAdapter($provider);

        if (! $provider->isConnected() && $provider->status !== MarketingProvider::STATUS_EXPIRED) {
            throw new InvalidArgumentException('Provider must be connected before saving asset selections.');
        }

        $validated = $adapter->validateAssetSelection($provider, $selection);

        return $this->updateCredentialConfiguration($provider, $validated);
    }

    /**
     * Replace credential.configuration without touching encrypted tokens.
     *
     * @param  array<string, mixed>  $configuration
     */
    public function updateCredentialConfiguration(
        MarketingProvider $provider,
        array $configuration,
    ): MarketingProviderCredential {
        return DB::transaction(function () use ($provider, $configuration) {
            $credential = $provider->credential ?? $provider->credential()->first();

            if (! $credential) {
                throw new InvalidArgumentException('Provider has no credentials to configure.');
            }

            $credential->configuration = $configuration;
            $credential->save();

            return $credential->fresh();
        });
    }

    public function supportsAssetDiscovery(MarketingProvider $provider): bool
    {
        if (! $this->registry->has($provider->slug)) {
            return false;
        }

        return $this->registry->resolve($provider->slug) instanceof MarketingProviderAssetDiscoveryInterface;
    }

    public function supportsLeadFormSync(MarketingProvider $provider): bool
    {
        if (! $this->registry->has($provider->slug)) {
            return false;
        }

        return $this->registry->resolve($provider->slug) instanceof MarketingProviderLeadFormSyncInterface;
    }

    public function supportsLeadImport(MarketingProvider $provider): bool
    {
        if (! $this->registry->has($provider->slug)) {
            return false;
        }

        return $this->registry->resolve($provider->slug) instanceof MarketingProviderLeadImportInterface;
    }

    /**
     * Manually import provider lead entries into CRM via LeadService.
     * Idempotent by (organization, provider, external_lead_id).
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function importLeadEntries(
        MarketingProvider $provider,
        User $user,
        array $options = [],
    ): array {
        $adapter = $this->requireLeadImportAdapter($provider);

        if (! $provider->isConnected() && $provider->status !== MarketingProvider::STATUS_EXPIRED) {
            throw new InvalidArgumentException('Provider must be connected before importing leads.');
        }

        $fetch = $adapter->importLeadEntries($provider, $options);

        if (($fetch['status'] ?? null) === MarketingProvider::STATUS_EXPIRED) {
            $this->markExpired($provider, $fetch['message'] ?? 'Credentials expired');
        } elseif (($fetch['status'] ?? null) === MarketingProvider::STATUS_DISCONNECTED) {
            $this->markDisconnected($provider);
        } elseif (($fetch['status'] ?? null) === MarketingProvider::STATUS_ERROR && ! ($fetch['ok'] ?? false)) {
            $this->markError($provider, $fetch['message'] ?? 'Lead import fetch failed');
        }

        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        foreach ($fetch['entries'] ?? [] as $entry) {
            if (! is_array($entry)) {
                $failed++;

                continue;
            }

            $outcome = $this->importNormalizedEntry($provider, $entry, $user);

            match ($outcome['result']) {
                'imported' => $imported++,
                'skipped' => $skipped++,
                default => $failed++,
            };

            if ($outcome['result'] === 'failed' && $outcome['error'] !== null) {
                $errors[] = [
                    'external_lead_id' => $entry['external_lead_id'] ?? null,
                    'error' => $outcome['error'],
                ];
            }
        }

        $status = MarketingProviderLeadImportRun::STATUS_COMPLETED;
        if ($imported === 0 && $failed > 0 && $skipped === 0) {
            $status = MarketingProviderLeadImportRun::STATUS_FAILED;
        } elseif ($failed > 0) {
            $status = MarketingProviderLeadImportRun::STATUS_PARTIAL;
        } elseif (! ($fetch['ok'] ?? false) && $imported === 0 && $skipped === 0) {
            $status = MarketingProviderLeadImportRun::STATUS_FAILED;
            $failed = max($failed, (int) ($fetch['failed'] ?? 1));
        }

        $message = $fetch['message']
            ?? sprintf('Imported %d, skipped %d, failed %d.', $imported, $skipped, $failed);

        $run = MarketingProviderLeadImportRun::query()->create([
            'organization_id' => $provider->organization_id,
            'marketing_provider_id' => $provider->id,
            'triggered_by' => $user->id,
            'imported_count' => $imported,
            'skipped_count' => $skipped,
            'failed_count' => $failed,
            'status' => $status,
            'message' => $message,
            'metadata' => [
                'fetched' => $fetch['fetched'] ?? count($fetch['entries'] ?? []),
                'fetch_failed' => $fetch['failed'] ?? 0,
                'errors' => array_slice($errors, 0, 25),
            ],
            'imported_at' => now(),
        ]);

        return [
            'ok' => $status !== MarketingProviderLeadImportRun::STATUS_FAILED,
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
            'status' => $status,
            'message' => $message,
            'run' => $run,
            'import_run_id' => $run->id,
        ];
    }

    public function latestLeadImportRun(MarketingProvider $provider): ?MarketingProviderLeadImportRun
    {
        return MarketingProviderLeadImportRun::query()
            ->where('organization_id', $provider->organization_id)
            ->where('marketing_provider_id', $provider->id)
            ->orderByDesc('imported_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * The single lead-creation pipeline shared by manual import (P7C.5) and
     * webhook processing (P7C.7). Idempotent by
     * (organization, provider, external_lead_id). There must be no other path
     * that creates CRM leads from provider entries.
     *
     * @param  array<string, mixed>  $entry  Normalized lead DTO from an adapter
     * @return array{result: string, lead_id: int|null, error: string|null}
     */
    protected function importNormalizedEntry(MarketingProvider $provider, array $entry, User $user): array
    {
        if (empty($entry['external_lead_id'])) {
            return ['result' => 'failed', 'lead_id' => null, 'error' => 'Lead entry is missing an external id.'];
        }

        if (empty($entry['fetch_ok'])) {
            return [
                'result' => 'failed',
                'lead_id' => null,
                'error' => is_string($entry['error'] ?? null) ? $entry['error'] : 'Lead entry fetch failed.',
            ];
        }

        $externalLeadId = (string) $entry['external_lead_id'];

        $alreadyImported = MarketingProviderImportedLead::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $provider->organization_id)
            ->where('marketing_provider_id', $provider->id)
            ->where('external_lead_id', $externalLeadId)
            ->exists();

        if ($alreadyImported) {
            return ['result' => 'skipped', 'lead_id' => null, 'error' => null];
        }

        try {
            $leadPayload = $this->mapEntryToLeadPayload($provider, $entry);

            if ($leadPayload['name'] === '') {
                throw new InvalidArgumentException('Lead entry is missing a usable name.');
            }

            $lead = $this->leads->create(
                $leadPayload,
                $user,
                \App\Models\AssignmentHistory::REASON_IMPORTED,
            );

            MarketingProviderImportedLead::query()->create([
                'organization_id' => $provider->organization_id,
                'marketing_provider_id' => $provider->id,
                'lead_id' => $lead->id,
                'external_lead_id' => $externalLeadId,
                'external_form_id' => $entry['external_form_id'] ?? null,
                'raw_payload' => $entry['raw'] ?? $entry,
                'imported_at' => now(),
            ]);

            return ['result' => 'imported', 'lead_id' => $lead->id, 'error' => null];
        } catch (Throwable $e) {
            return ['result' => 'failed', 'lead_id' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    protected function mapEntryToLeadPayload(MarketingProvider $provider, array $entry): array
    {
        $fields = is_array($entry['fields'] ?? null) ? $entry['fields'] : [];
        $unmapped = is_array($entry['unmapped_fields'] ?? null) ? $entry['unmapped_fields'] : [];

        $fullName = trim((string) ($fields['full_name'] ?? ''));
        if ($fullName === '') {
            $fullName = trim(
                trim((string) ($fields['first_name'] ?? '')).' '.trim((string) ($fields['last_name'] ?? ''))
            );
        }

        $email = isset($fields['email']) && is_string($fields['email']) ? trim($fields['email']) : null;
        $phone = $fields['phone_number'] ?? $fields['phone'] ?? null;
        $phone = is_string($phone) ? trim($phone) : null;
        $company = $fields['company_name'] ?? $fields['company'] ?? null;
        $company = is_string($company) ? trim($company) : null;

        if ($fullName === '') {
            if ($email) {
                $fullName = strstr($email, '@', true) ?: $email;
            } else {
                $fullName = 'Meta Lead '.$entry['external_lead_id'];
            }
        }

        return [
            'organization_id' => $provider->organization_id,
            'name' => $fullName,
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
            'company' => $company !== '' ? $company : null,
            'source' => 'facebook',
            'status' => 'new',
            'priority' => 'medium',
            'custom_fields' => [
                'provider' => [
                    'slug' => $provider->slug,
                    'external_lead_id' => $entry['external_lead_id'],
                    'external_form_id' => $entry['external_form_id'] ?? null,
                    'external_page_id' => $entry['external_page_id'] ?? null,
                    'created_time' => $entry['created_time'] ?? null,
                    'unmapped_fields' => $unmapped,
                    'ad_id' => $entry['raw']['ad_id'] ?? null,
                    'ad_name' => $entry['raw']['ad_name'] ?? null,
                ],
            ],
        ];
    }

    /**
     * Synchronize selected lead-form metadata into the local catalog.
     * Idempotent: upserts by (organization, provider, external_form_id).
     * Missing/deselected forms are marked inactive — never hard-deleted.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function synchronizeLeadForms(MarketingProvider $provider, array $options = []): array
    {
        $adapter = $this->requireLeadFormSyncAdapter($provider);

        if (! $provider->isConnected() && $provider->status !== MarketingProvider::STATUS_EXPIRED) {
            throw new InvalidArgumentException('Provider must be connected before synchronizing lead forms.');
        }

        $result = $adapter->synchronizeLeadForms($provider, $options);

        if (($result['status'] ?? null) === MarketingProvider::STATUS_EXPIRED) {
            $this->markExpired($provider, $result['message'] ?? 'Credentials expired');
        } elseif (($result['status'] ?? null) === MarketingProvider::STATUS_DISCONNECTED) {
            $this->markDisconnected($provider);
        } elseif (($result['status'] ?? null) === MarketingProvider::STATUS_ERROR && ! ($result['ok'] ?? false)) {
            $this->markError($provider, $result['message'] ?? 'Lead form synchronization failed');
        }

        $selectedIds = $this->selectedLeadFormIds($provider);

        DB::transaction(function () use ($provider, $result, $selectedIds) {
            foreach ($result['forms'] ?? [] as $form) {
                if (! is_array($form) || empty($form['external_form_id'])) {
                    continue;
                }

                $externalId = (string) $form['external_form_id'];

                if (! empty($form['sync_ok'])) {
                    $this->upsertLeadFormCatalogRow($provider, $form);

                    continue;
                }

                if (! empty($form['missing'])) {
                    $this->markLeadFormInactive($provider, $externalId);
                }
            }

            // Deselected forms stay in history but become inactive.
            $activeForms = MarketingProviderLeadForm::query()
                ->where('marketing_provider_id', $provider->id)
                ->where('status', MarketingProviderLeadForm::STATUS_ACTIVE)
                ->get();

            foreach ($activeForms as $existing) {
                if (! in_array($existing->external_form_id, $selectedIds, true)) {
                    $existing->status = MarketingProviderLeadForm::STATUS_INACTIVE;
                    $existing->save();
                }
            }

            $provider->last_synced_at = now();
            $provider->save();
        });

        if (($result['ok'] ?? false) && $provider->status !== MarketingProvider::STATUS_CONNECTED) {
            if ($provider->status !== MarketingProvider::STATUS_EXPIRED
                && $provider->status !== MarketingProvider::STATUS_DISCONNECTED) {
                $this->markConnected($provider);
            }
        }

        $result['catalog_count'] = MarketingProviderLeadForm::query()
            ->where('marketing_provider_id', $provider->id)
            ->count();

        return $result;
    }

    /**
     * @return Collection<int, MarketingProviderLeadForm>
     */
    public function listLeadForms(MarketingProvider $provider, bool $activeOnly = false): Collection
    {
        $query = MarketingProviderLeadForm::query()
            ->where('organization_id', $provider->organization_id)
            ->where('marketing_provider_id', $provider->id)
            ->orderBy('name')
            ->orderBy('external_form_id');

        if ($activeOnly) {
            $query->where('status', MarketingProviderLeadForm::STATUS_ACTIVE);
        }

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $form
     */
    protected function upsertLeadFormCatalogRow(MarketingProvider $provider, array $form): MarketingProviderLeadForm
    {
        $externalId = (string) $form['external_form_id'];

        $row = MarketingProviderLeadForm::query()->updateOrCreate(
            [
                'organization_id' => $provider->organization_id,
                'marketing_provider_id' => $provider->id,
                'external_form_id' => $externalId,
            ],
            [
                'external_page_id' => $form['external_page_id'] ?? null,
                'name' => $form['name'] ?? null,
                'status' => MarketingProviderLeadForm::STATUS_ACTIVE,
                'locale' => $form['locale'] ?? null,
                'questions' => $form['questions'] ?? [],
                'raw_metadata' => $form['raw_metadata'] ?? [
                    'provider_status' => $form['provider_status'] ?? null,
                ],
                'last_synced_at' => now(),
            ],
        );

        return $row;
    }

    protected function markLeadFormInactive(MarketingProvider $provider, string $externalFormId): void
    {
        MarketingProviderLeadForm::query()
            ->where('marketing_provider_id', $provider->id)
            ->where('external_form_id', $externalFormId)
            ->update(['status' => MarketingProviderLeadForm::STATUS_INACTIVE]);
    }

    /**
     * @return list<string>
     */
    protected function selectedLeadFormIds(MarketingProvider $provider): array
    {
        $configuration = $provider->credential?->configuration
            ?? $provider->credential()->first()?->configuration
            ?? [];

        $ids = $configuration['lead_form_ids'] ?? [];

        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('strval', $ids), fn (string $id) => $id !== '')));
    }

    public function credentialsAreExpired(MarketingProvider $provider): bool
    {
        $credential = $provider->credential ?? $provider->credential()->first();

        return $credential?->isExpired() ?? false;
    }

    /**
     * Provider-agnostic integration cards for the tenant Integrations UI.
     *
     * Merges platform catalog with this organization's connection rows.
     * Does not expose access/refresh tokens.
     *
     * @return list<array{
     *     slug: string,
     *     name: string,
     *     channel: string|null,
     *     status: string,
     *     status_label: string,
     *     connectable: bool,
     *     connected: bool,
     *     external_account_id: string|null,
     *     connected_at: string|null,
     *     disconnected_at: string|null,
     *     expires_at: string|null,
     *     last_error: string|null,
     *     provider_id: int|null
     * }>
     */
    public function integrationCardsForOrganization(Organization $organization): array
    {
        $connections = $this->listProviders($organization)->load('credential')->keyBy('slug');
        $cards = [];

        foreach ($this->catalog() as $slug => $meta) {
            /** @var MarketingProvider|null $connection */
            $connection = $connections->get($slug);
            $connectable = $this->registry->has($slug);
            $credential = $connection?->credential;

            $status = $connection?->status ?? MarketingProvider::STATUS_DISCONNECTED;

            $cards[] = [
                'slug' => $slug,
                'name' => $meta['name'] ?? $slug,
                'channel' => $meta['channel'] ?? null,
                'status' => $status,
                'status_label' => $connection?->statusLabel()
                    ?? (new MarketingProvider(['status' => $status]))->statusLabel(),
                'connectable' => $connectable,
                'connected' => $status === MarketingProvider::STATUS_CONNECTED,
                'external_account_id' => $connection?->external_account_id,
                'connected_at' => $connection?->connected_at?->toIso8601String(),
                'disconnected_at' => $connection?->disconnected_at?->toIso8601String(),
                'expires_at' => $credential?->expires_at?->toIso8601String(),
                'last_error' => $connection?->last_error,
                'provider_id' => $connection?->id,
                'configuration' => $credential?->configuration ?? [],
            ];
        }

        return $cards;
    }

    /**
     * Safe detail payload for UI (never includes tokens).
     *
     * @return array<string, mixed>|null
     */
    public function integrationDetailsForOrganization(Organization $organization, string $slug): ?array
    {
        $cards = collect($this->integrationCardsForOrganization($organization))->keyBy('slug');

        return $cards->get($slug);
    }

    /**
     * Planned provider catalog (not yet registered as drivers).
     *
     * @return array<string, array{name: string, channel: string|null}>
     */
    public function catalog(): array
    {
        return config('marketing.providers.catalog', []);
    }

    /**
     * @return list<array{slug: string, name: string, capabilities: list<string>}>
     */
    public function registeredProviders(): array
    {
        return $this->registry->supported();
    }

    /**
     * @return list<string>
     */
    public function supportedStatuses(): array
    {
        return MarketingProvider::STATUSES;
    }

    public function resolveAdapter(string $slug): MarketingProviderInterface
    {
        return $this->registry->resolve($slug);
    }

    protected function requireAdapter(MarketingProvider $provider): MarketingProviderInterface
    {
        if (! $this->registry->has($provider->slug)) {
            throw new InvalidArgumentException(
                "Marketing provider [{$provider->slug}] has no registered adapter."
            );
        }

        return $this->registry->resolve($provider->slug);
    }

    protected function requireAssetDiscoveryAdapter(MarketingProvider $provider): MarketingProviderAssetDiscoveryInterface
    {
        $adapter = $this->requireAdapter($provider);

        if (! $adapter instanceof MarketingProviderAssetDiscoveryInterface) {
            throw new InvalidArgumentException(
                "Marketing provider [{$provider->slug}] does not support asset discovery."
            );
        }

        return $adapter;
    }

    protected function requireLeadFormSyncAdapter(MarketingProvider $provider): MarketingProviderLeadFormSyncInterface
    {
        $adapter = $this->requireAdapter($provider);

        if (! $adapter instanceof MarketingProviderLeadFormSyncInterface) {
            throw new InvalidArgumentException(
                "Marketing provider [{$provider->slug}] does not support lead form synchronization."
            );
        }

        return $adapter;
    }

    protected function requireLeadImportAdapter(MarketingProvider $provider): MarketingProviderLeadImportInterface
    {
        $adapter = $this->requireAdapter($provider);

        if (! $adapter instanceof MarketingProviderLeadImportInterface) {
            throw new InvalidArgumentException(
                "Marketing provider [{$provider->slug}] does not support lead import."
            );
        }

        return $adapter;
    }

    protected function requireSynchronizationAdapter(
        MarketingProvider $provider,
    ): MarketingProviderSynchronizationInterface {
        $adapter = $this->requireAdapter($provider);

        if (! $adapter instanceof MarketingProviderSynchronizationInterface) {
            throw new InvalidArgumentException(
                "Marketing provider [{$provider->slug}] does not support synchronization."
            );
        }

        return $adapter;
    }

    protected function requireWebhookAdapter(string $slug): MarketingProviderWebhookInterface
    {
        if (! $this->registry->has($slug)) {
            throw new InvalidArgumentException(
                "Marketing provider [{$slug}] has no registered adapter."
            );
        }

        $adapter = $this->registry->resolve($slug);

        if (! $adapter instanceof MarketingProviderWebhookInterface) {
            throw new InvalidArgumentException(
                "Marketing provider [{$slug}] does not support webhooks."
            );
        }

        return $adapter;
    }

    protected function catalogName(string $slug): ?string
    {
        $catalog = $this->catalog();

        return $catalog[$slug]['name'] ?? null;
    }
}
