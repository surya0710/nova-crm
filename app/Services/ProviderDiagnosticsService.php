<?php

namespace App\Services;

use App\Models\MarketingProvider;
use App\Models\MarketingProviderCredential;
use App\Models\MarketingProviderImportedLead;
use App\Models\MarketingProviderLeadImportRun;
use App\Models\MarketingProviderSyncRun;
use App\Models\MarketingProviderUploadedConversion;
use App\Models\MarketingProviderWebhookEvent;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

/**
 * Provider-agnostic diagnostics aggregation (P7E.1).
 *
 * Normalizes health, credentials, synchronization history, runtime statistics,
 * and recent errors for every marketing provider. No provider-specific logic.
 */
class ProviderDiagnosticsService
{
    public const HEALTH_DISCONNECTED = 'disconnected';

    public const HEALTH_HEALTHY = 'healthy';

    public const HEALTH_DEGRADED = 'degraded';

    public const HEALTH_UNHEALTHY = 'unhealthy';

    public const HEALTH_EXPIRED_CREDENTIALS = 'expired_credentials';

    public const HEALTH_REVOKED_CREDENTIALS = 'revoked_credentials';

    public function __construct(
        protected MarketingProviderService $providers,
    ) {}

    /**
     * @return array{
     *     generated_at: string,
     *     organization_id: int,
     *     providers: list<array<string, mixed>>,
     *     summary: array<string, int>
     * }
     */
    public function diagnosticsForOrganization(Organization $organization): array
    {
        $cards = $this->providers->integrationCardsForOrganization($organization);
        $connections = MarketingProvider::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organization->id)
            ->orderBy('slug')
            ->with('credential')
            ->get()
            ->keyBy('slug');
        $providers = [];

        foreach ($cards as $card) {
            /** @var MarketingProvider|null $connection */
            $connection = $connections->get($card['slug']);
            $providers[] = $this->buildProviderDiagnostics($organization, $card, $connection);
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'organization_id' => $organization->id,
            'providers' => $providers,
            'summary' => $this->summarizeProviders($providers),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function diagnosticsForProvider(MarketingProvider $provider): array
    {
        $organization = $provider->organization;

        if ($organization === null) {
            throw new InvalidArgumentException('Provider must belong to an organization.');
        }

        $card = $this->providers->integrationDetailsForOrganization($organization, $provider->slug);

        if ($card === null) {
            throw new InvalidArgumentException("Provider slug [{$provider->slug}] is not in the integration catalog.");
        }

        return $this->buildProviderDiagnostics($organization, $card, $provider->load('credential'));
    }

    /**
     * Invoke adapter health check and refresh normalized diagnostics.
     *
     * @return array{
     *     health_check: array<string, mixed>,
     *     diagnostics: array<string, mixed>
     * }
     */
    public function runHealthCheck(MarketingProvider $provider): array
    {
        $healthCheck = $this->providers->checkHealth($provider->fresh());

        return [
            'health_check' => $healthCheck,
            'diagnostics' => $this->diagnosticsForProvider($provider->fresh(['credential'])),
        ];
    }

    /**
     * @param  array<string, mixed>  $card
     * @return array<string, mixed>
     */
    protected function buildProviderDiagnostics(
        Organization $organization,
        array $card,
        ?MarketingProvider $connection,
    ): array {
        $credential = $connection?->credential;
        $health = $this->normalizeHealth($connection, $credential);
        $synchronization = $connection
            ? $this->synchronizationSummary($connection)
            : $this->emptySynchronizationSummary();
        $statistics = $connection
            ? $this->runtimeStatistics($connection)
            : $this->emptyRuntimeStatistics();
        $errors = $connection
            ? $this->recentErrors($connection)
            : [];

        return [
            'slug' => $card['slug'],
            'name' => $card['name'],
            'channel' => $card['channel'] ?? null,
            'connectable' => (bool) ($card['connectable'] ?? false),
            'provider_id' => $connection?->id,
            'connection' => [
                'status' => $card['status'],
                'status_label' => $card['status_label'],
                'connected' => (bool) ($card['connected'] ?? false),
                'external_account_id' => $card['external_account_id'] ?? null,
                'connected_at' => $card['connected_at'] ?? null,
                'disconnected_at' => $card['disconnected_at'] ?? null,
            ],
            'health' => $health,
            'credentials' => $this->credentialStatus($connection, $credential, $health),
            'synchronization' => $synchronization,
            'statistics' => $statistics,
            'errors' => $errors,
            'highlights' => $this->operationalHighlights($synchronization, $health),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizeHealth(
        ?MarketingProvider $provider,
        ?MarketingProviderCredential $credential,
    ): array {
        if ($provider === null) {
            return [
                'state' => self::HEALTH_DISCONNECTED,
                'label' => 'Disconnected',
                'healthy' => false,
                'degraded' => false,
                'last_health_check_at' => null,
                'last_error' => null,
            ];
        }

        $state = $this->resolveHealthState($provider, $credential);

        return [
            'state' => $state,
            'label' => $this->healthLabel($state),
            'healthy' => $state === self::HEALTH_HEALTHY,
            'degraded' => $state === self::HEALTH_DEGRADED,
            'last_health_check_at' => $provider->last_health_at?->toIso8601String(),
            'last_error' => $provider->last_error,
        ];
    }

    protected function resolveHealthState(
        MarketingProvider $provider,
        ?MarketingProviderCredential $credential,
    ): string {
        if ($this->credentialsAreRevoked($provider, $credential)) {
            return self::HEALTH_REVOKED_CREDENTIALS;
        }

        if ($provider->status === MarketingProvider::STATUS_DISCONNECTED) {
            return self::HEALTH_DISCONNECTED;
        }

        if ($provider->status === MarketingProvider::STATUS_EXPIRED
            || ($credential !== null && $credential->isExpired())) {
            return self::HEALTH_EXPIRED_CREDENTIALS;
        }

        if ($provider->status === MarketingProvider::STATUS_ERROR) {
            return self::HEALTH_UNHEALTHY;
        }

        if ($provider->status === MarketingProvider::STATUS_CONNECTED && $this->isDegraded($provider)) {
            return self::HEALTH_DEGRADED;
        }

        if ($provider->status === MarketingProvider::STATUS_CONNECTED) {
            return self::HEALTH_HEALTHY;
        }

        return self::HEALTH_UNHEALTHY;
    }

    protected function isDegraded(MarketingProvider $provider): bool
    {
        $latestRun = MarketingProviderSyncRun::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $provider->organization_id)
            ->where('marketing_provider_id', $provider->id)
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();

        if ($latestRun?->status === MarketingProviderSyncRun::STATUS_PARTIAL) {
            return true;
        }

        $recentPartialCount = MarketingProviderSyncRun::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $provider->organization_id)
            ->where('marketing_provider_id', $provider->id)
            ->where('status', MarketingProviderSyncRun::STATUS_PARTIAL)
            ->where('started_at', '>=', now()->subDays(7))
            ->count();

        return $recentPartialCount > 0;
    }

    protected function credentialsAreRevoked(
        MarketingProvider $provider,
        ?MarketingProviderCredential $credential,
    ): bool {
        if (($credential?->metadata['revoked'] ?? false) === true) {
            return true;
        }

        $message = strtolower((string) ($provider->last_error ?? ''));

        return str_contains($message, 'revoked')
            || str_contains($message, 'invalid_grant');
    }

    protected function healthLabel(string $state): string
    {
        return match ($state) {
            self::HEALTH_HEALTHY => 'Healthy',
            self::HEALTH_DEGRADED => 'Degraded',
            self::HEALTH_UNHEALTHY => 'Unhealthy',
            self::HEALTH_EXPIRED_CREDENTIALS => 'Expired Credentials',
            self::HEALTH_REVOKED_CREDENTIALS => 'Revoked Credentials',
            default => 'Disconnected',
        };
    }

    /**
     * @param  array<string, mixed>  $health
     * @return array<string, mixed>
     */
    protected function credentialStatus(
        ?MarketingProvider $provider,
        ?MarketingProviderCredential $credential,
        array $health,
    ): array {
        $hasAccessToken = $credential !== null
            && is_string($credential->access_token)
            && $credential->access_token !== '';

        return [
            'oauth_connected' => $provider !== null
                && $provider->status === MarketingProvider::STATUS_CONNECTED
                && $hasAccessToken,
            'expires_at' => $credential?->expires_at?->toIso8601String(),
            'is_expired' => $credential?->isExpired() ?? false,
            'refresh_token_available' => $credential !== null
                && is_string($credential->refresh_token)
                && $credential->refresh_token !== '',
            'last_health_check_at' => $provider?->last_health_at?->toIso8601String(),
            'last_refresh_at' => $this->lastCredentialRefreshAt($credential),
            'is_revoked' => $health['state'] === self::HEALTH_REVOKED_CREDENTIALS,
        ];
    }

    protected function lastCredentialRefreshAt(?MarketingProviderCredential $credential): ?string
    {
        if ($credential === null) {
            return null;
        }

        $metadataRefresh = $credential->metadata['last_refreshed_at'] ?? null;

        if (is_string($metadataRefresh) && $metadataRefresh !== '') {
            return $metadataRefresh;
        }

        if (! is_string($credential->refresh_token) || $credential->refresh_token === '') {
            return null;
        }

        return $credential->updated_at?->toIso8601String();
    }

    /**
     * @return array<string, mixed>
     */
    protected function synchronizationSummary(MarketingProvider $provider): array
    {
        $baseQuery = MarketingProviderSyncRun::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $provider->organization_id)
            ->where('marketing_provider_id', $provider->id);

        $last = (clone $baseQuery)
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();

        $lastSuccessful = (clone $baseQuery)
            ->whereIn('status', [
                MarketingProviderSyncRun::STATUS_COMPLETED,
                MarketingProviderSyncRun::STATUS_PARTIAL,
            ])
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();

        $lastFailed = (clone $baseQuery)
            ->where('status', MarketingProviderSyncRun::STATUS_FAILED)
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();

        $lastUpload = (clone $baseQuery)
            ->where('sync_type', MarketingProviderSyncRun::TYPE_CONVERSION_UPLOAD)
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();

        $lastImportRun = MarketingProviderLeadImportRun::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $provider->organization_id)
            ->where('marketing_provider_id', $provider->id)
            ->orderByDesc('imported_at')
            ->orderByDesc('id')
            ->first();

        return [
            'last' => $this->normalizeSyncRun($last),
            'last_successful' => $this->normalizeSyncRun($lastSuccessful),
            'last_failed' => $this->normalizeSyncRun($lastFailed),
            'last_upload' => $this->normalizeSyncRun($lastUpload),
            'last_import' => $this->normalizeLeadImportRun($lastImportRun),
            'recent' => $this->normalizeSyncRuns(
                MarketingProviderSyncRun::query()
                    ->withoutGlobalScope(OrganizationScope::class)
                    ->where('organization_id', $provider->organization_id)
                    ->where('marketing_provider_id', $provider->id)
                    ->orderByDesc('started_at')
                    ->orderByDesc('id')
                    ->limit(10)
                    ->get(),
            ),
        ];
    }

    /**
     * @return array<string, null>
     */
    protected function emptySynchronizationSummary(): array
    {
        return [
            'last' => null,
            'last_successful' => null,
            'last_failed' => null,
            'last_upload' => null,
            'last_import' => null,
            'recent' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function runtimeStatistics(MarketingProvider $provider): array
    {
        $syncQuery = MarketingProviderSyncRun::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $provider->organization_id)
            ->where('marketing_provider_id', $provider->id);

        $syncCount = (clone $syncQuery)->count();
        $successCount = (clone $syncQuery)->whereIn('status', [
            MarketingProviderSyncRun::STATUS_COMPLETED,
            MarketingProviderSyncRun::STATUS_PARTIAL,
        ])->count();
        $failureCount = (clone $syncQuery)->where('status', MarketingProviderSyncRun::STATUS_FAILED)->count();

        $importedLeads = MarketingProviderImportedLead::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $provider->organization_id)
            ->where('marketing_provider_id', $provider->id)
            ->count();

        $webhookEventsProcessed = MarketingProviderWebhookEvent::query()
            ->where('organization_id', $provider->organization_id)
            ->where('provider', $provider->slug)
            ->where('processing_status', MarketingProviderWebhookEvent::STATUS_PROCESSED)
            ->count();

        $uploadedConversions = MarketingProviderUploadedConversion::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $provider->organization_id)
            ->where('marketing_provider_id', $provider->id)
            ->count();

        return [
            'inbound' => [
                'imported_leads' => $importedLeads,
                'webhook_events_processed' => $webhookEventsProcessed,
            ],
            'outbound' => [
                'uploaded_conversions' => $uploadedConversions,
            ],
            'general' => [
                'synchronization_count' => $syncCount,
                'success_count' => $successCount,
                'failure_count' => $failureCount,
            ],
        ];
    }

    /**
     * @return array{
     *     inbound: array{imported_leads: int, webhook_events_processed: int},
     *     outbound: array{uploaded_conversions: int},
     *     general: array{synchronization_count: int, success_count: int, failure_count: int}
     * }
     */
    protected function emptyRuntimeStatistics(): array
    {
        return [
            'inbound' => [
                'imported_leads' => 0,
                'webhook_events_processed' => 0,
            ],
            'outbound' => [
                'uploaded_conversions' => 0,
            ],
            'general' => [
                'synchronization_count' => 0,
                'success_count' => 0,
                'failure_count' => 0,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function recentErrors(MarketingProvider $provider): array
    {
        $errors = [];

        if (is_string($provider->last_error) && $provider->last_error !== '') {
            $errors[] = [
                'type' => 'provider',
                'message' => $provider->last_error,
                'occurred_at' => $provider->updated_at?->toIso8601String(),
            ];
        }

        $failedRuns = MarketingProviderSyncRun::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $provider->organization_id)
            ->where('marketing_provider_id', $provider->id)
            ->where('status', MarketingProviderSyncRun::STATUS_FAILED)
            ->whereNotNull('message')
            ->orderByDesc('finished_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        foreach ($failedRuns as $run) {
            $errors[] = [
                'type' => 'synchronization_failure',
                'message' => (string) $run->message,
                'occurred_at' => ($run->finished_at ?? $run->started_at)?->toIso8601String(),
                'sync_type' => $run->sync_type,
            ];
        }

        $failedImports = MarketingProviderLeadImportRun::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $provider->organization_id)
            ->where('marketing_provider_id', $provider->id)
            ->where('status', MarketingProviderLeadImportRun::STATUS_FAILED)
            ->whereNotNull('message')
            ->orderByDesc('imported_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        foreach ($failedImports as $run) {
            $errors[] = [
                'type' => 'lead_import_failure',
                'message' => (string) $run->message,
                'occurred_at' => $run->imported_at?->toIso8601String(),
            ];
        }

        $failedWebhooks = MarketingProviderWebhookEvent::query()
            ->where('organization_id', $provider->organization_id)
            ->where('provider', $provider->slug)
            ->where('processing_status', MarketingProviderWebhookEvent::STATUS_FAILED)
            ->whereNotNull('failure_reason')
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        foreach ($failedWebhooks as $event) {
            $errors[] = [
                'type' => 'webhook_failure',
                'message' => (string) $event->failure_reason,
                'occurred_at' => ($event->processed_at ?? $event->received_at)?->toIso8601String(),
            ];
        }

        usort($errors, function (array $left, array $right): int {
            return strcmp((string) ($right['occurred_at'] ?? ''), (string) ($left['occurred_at'] ?? ''));
        });

        return array_slice($errors, 0, 10);
    }

    /**
     * @param  array<string, mixed>  $synchronization
     * @param  array<string, mixed>  $health
     * @return array<string, string|null>
     */
    protected function operationalHighlights(array $synchronization, array $health): array
    {
        return [
            'last_upload_at' => $synchronization['last_upload']['finished_at']
                ?? $synchronization['last_upload']['started_at']
                ?? null,
            'last_import_at' => $synchronization['last_import']['imported_at'] ?? null,
            'last_health_check_at' => $health['last_health_check_at'] ?? null,
            'last_synchronization_at' => $synchronization['last']['finished_at']
                ?? $synchronization['last']['started_at']
                ?? null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $providers
     * @return array<string, int>
     */
    protected function summarizeProviders(array $providers): array
    {
        $summary = [
            'total' => count($providers),
            'connected' => 0,
            'healthy' => 0,
            'degraded' => 0,
            'unhealthy' => 0,
            'expired' => 0,
            'revoked' => 0,
            'disconnected' => 0,
        ];

        foreach ($providers as $provider) {
            $connectionStatus = $provider['connection']['status'] ?? MarketingProvider::STATUS_DISCONNECTED;

            if ($connectionStatus === MarketingProvider::STATUS_CONNECTED) {
                $summary['connected']++;
            }

            match ($provider['health']['state'] ?? self::HEALTH_DISCONNECTED) {
                self::HEALTH_HEALTHY => $summary['healthy']++,
                self::HEALTH_DEGRADED => $summary['degraded']++,
                self::HEALTH_UNHEALTHY => $summary['unhealthy']++,
                self::HEALTH_EXPIRED_CREDENTIALS => $summary['expired']++,
                self::HEALTH_REVOKED_CREDENTIALS => $summary['revoked']++,
                default => $summary['disconnected']++,
            };
        }

        return $summary;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function normalizeSyncRun(?MarketingProviderSyncRun $run): ?array
    {
        if ($run === null) {
            return null;
        }

        return [
            'id' => $run->id,
            'sync_type' => $run->sync_type,
            'direction' => $run->direction,
            'status' => $run->status,
            'started_at' => $run->started_at?->toIso8601String(),
            'finished_at' => $run->finished_at?->toIso8601String(),
            'duration_seconds' => $run->durationInSeconds(),
            'records_processed' => $run->records_processed,
            'records_succeeded' => $run->records_succeeded,
            'records_failed' => $run->records_failed,
            'message' => $run->message,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function normalizeLeadImportRun(?MarketingProviderLeadImportRun $run): ?array
    {
        if ($run === null) {
            return null;
        }

        return [
            'id' => $run->id,
            'status' => $run->status,
            'imported_at' => $run->imported_at?->toIso8601String(),
            'imported_count' => $run->imported_count,
            'skipped_count' => $run->skipped_count,
            'failed_count' => $run->failed_count,
            'message' => $run->message,
        ];
    }

    /**
     * @param  Collection<int, MarketingProviderSyncRun>  $runs
     * @return list<array<string, mixed>>
     */
    protected function normalizeSyncRuns(Collection $runs): array
    {
        return $runs
            ->map(fn (MarketingProviderSyncRun $run) => $this->normalizeSyncRun($run))
            ->filter()
            ->values()
            ->all();
    }
}
