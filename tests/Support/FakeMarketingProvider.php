<?php

namespace Tests\Support;

use App\Contracts\MarketingProviderInterface;
use App\Contracts\MarketingProviderSynchronizationInterface;
use App\Models\MarketingProvider;
use RuntimeException;

/**
 * Test-only provider adapter. No production Meta/Google/LinkedIn logic.
 */
class FakeMarketingProvider implements MarketingProviderInterface, MarketingProviderSynchronizationInterface
{
    public bool $healthy = true;

    public string $healthStatus = 'connected';

    public string $healthMessage = 'ok';

    public bool $syncOk = true;

    public bool $throwDuringSync = false;

    /** @var array<string, mixed>|null */
    public ?array $syncResult = null;

    public bool $revokeCalled = false;

    public int $authorizeCalls = 0;

    public int $refreshCalls = 0;

    public int $syncCalls = 0;

    public int $webhookCalls = 0;

    public int $uploadCalls = 0;

    public int $healthCalls = 0;

    /** @var array<string, mixed> */
    public array $authorizeResult = [
        'authorization_url' => 'https://example.test/oauth/authorize',
    ];

    /** @var array<string, mixed> */
    public array $refreshResult = [
        'access_token' => 'refreshed-access',
        'refresh_token' => 'refreshed-refresh',
        'expires_at' => null,
        'token_type' => 'Bearer',
        'scopes' => ['ads_read'],
    ];

    public function __construct(
        protected string $providerSlug = 'fake',
        protected string $name = 'Fake Provider',
        /** @var list<string> */
        protected array $caps = ['oauth', 'sync', 'webhooks', 'offline_conversions'],
    ) {
        $this->refreshResult['expires_at'] = now()->addHour()->toIso8601String();
    }

    public function slug(): string
    {
        return $this->providerSlug;
    }

    public function displayName(): string
    {
        return $this->name;
    }

    public function capabilities(): array
    {
        return $this->caps;
    }

    public function authorize(MarketingProvider $provider, array $context = []): array
    {
        $this->authorizeCalls++;

        return $this->authorizeResult;
    }

    public function refreshCredentials(MarketingProvider $provider): array
    {
        $this->refreshCalls++;

        return $this->refreshResult;
    }

    public function revoke(MarketingProvider $provider): void
    {
        $this->revokeCalled = true;
    }

    public function synchronize(MarketingProvider $provider, array $options = []): array
    {
        $this->syncCalls++;

        if ($this->throwDuringSync) {
            throw new RuntimeException('unexpected sync failure');
        }

        if ($this->syncResult !== null) {
            return $this->syncResult;
        }

        return [
            'ok' => $this->syncOk,
            'accounts' => $this->syncOk ? [['external_id' => 'acc-1', 'name' => 'Account']] : [],
            'campaigns' => [],
            'message' => $this->syncOk ? null : 'sync failed',
        ];
    }

    public function receiveWebhook(MarketingProvider $provider, array $payload, array $headers = []): array
    {
        $this->webhookCalls++;

        return [
            'ok' => true,
            'event' => $payload['event'] ?? 'unknown',
            'normalized' => $payload,
            'message' => null,
        ];
    }

    public function uploadConversions(MarketingProvider $provider, array $conversions): array
    {
        $this->uploadCalls++;

        return [
            'ok' => true,
            'uploaded' => count($conversions),
            'failed' => 0,
            'message' => null,
            'results' => [],
        ];
    }

    public function reportHealth(MarketingProvider $provider): array
    {
        $this->healthCalls++;

        return [
            'healthy' => $this->healthy,
            'status' => $this->healthStatus,
            'message' => $this->healthMessage,
            'checked_at' => now()->toIso8601String(),
            'metadata' => [],
        ];
    }
}
