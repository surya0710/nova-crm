<?php

namespace App\Services\Recruitment\Providers;

use App\Contracts\RecruitmentProviderInterface;
use App\Models\RecruitmentProvider;

/**
 * Shared adapter helpers. Adapters never persist Eloquent models.
 */
abstract class AbstractRecruitmentProvider implements RecruitmentProviderInterface
{
    abstract public function slug(): string;

    abstract public function displayName(): string;

    abstract public function category(): string;

    /**
     * @return list<string>
     */
    abstract public function capabilities(): array;

    public function authorize(RecruitmentProvider $provider, array $context = []): array
    {
        $phase = $context['phase'] ?? 'start';

        if ($phase === 'callback') {
            return [
                'credentials' => [
                    'access_token' => (string) ($context['code'] ?? 'placeholder-token'),
                    'token_type' => 'Bearer',
                    'expires_at' => now()->addYear()->toIso8601String(),
                    'metadata' => ['connected_via' => 'placeholder'],
                ],
                'status' => RecruitmentProvider::STATUS_CONNECTED,
            ];
        }

        return [
            'authorization_url' => null,
            'credentials' => [
                'access_token' => 'placeholder-'. $this->slug(),
                'token_type' => 'Bearer',
                'expires_at' => now()->addYear()->toIso8601String(),
                'metadata' => ['connection_mode' => 'api_key_placeholder'],
            ],
            'status' => RecruitmentProvider::STATUS_CONNECTED,
            'metadata' => ['note' => 'Placeholder connection — live OAuth will replace this in a future release.'],
        ];
    }

    public function refreshCredentials(RecruitmentProvider $provider): array
    {
        return [
            'access_token' => 'refreshed-'.$this->slug(),
            'expires_at' => now()->addYear()->toIso8601String(),
            'metadata' => ['refreshed_at' => now()->toIso8601String()],
        ];
    }

    public function revoke(RecruitmentProvider $provider): void
    {
        // Best-effort placeholder — no remote revoke until live vendors are wired.
    }

    public function synchronize(RecruitmentProvider $provider, array $options = []): array
    {
        return [
            'ok' => true,
            'message' => 'Synchronization placeholder completed.',
            'metadata' => ['provider' => $this->slug()],
        ];
    }

    public function reportHealth(RecruitmentProvider $provider): array
    {
        return [
            'healthy' => $provider->isConnected(),
            'status' => $provider->isConnected() ? 'healthy' : 'disconnected',
            'message' => $provider->isConnected()
                ? $this->displayName().' connection is healthy (placeholder).'
                : $this->displayName().' is disconnected.',
            'checked_at' => now()->toIso8601String(),
            'metadata' => ['provider' => $this->slug()],
        ];
    }
}
