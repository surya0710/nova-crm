<?php

namespace App\Services\Recruitment;

use App\Models\Organization;
use App\Models\RecruitmentProvider;
use App\Models\RecruitmentProviderCredential;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Recruitment\Providers\RecruitmentProviderRegistry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Single write authority for recruitment provider connections and credentials.
 * Mirrors MarketingProviderService patterns without duplicating marketing tables.
 */
class RecruitmentProviderService
{
    public function __construct(
        protected RecruitmentProviderRegistry $registry,
        protected AuditLogger $auditLogger,
    ) {}

    public function registerProvider(
        Organization $organization,
        string $slug,
        array $attributes = [],
        ?User $actor = null,
    ): RecruitmentProvider {
        $existing = $this->findProvider($organization, $slug);

        if ($existing) {
            return $existing;
        }

        $catalog = config("recruitment.providers.catalog.{$slug}", []);
        $adapter = $this->registry->has($slug) ? $this->registry->resolve($slug) : null;

        $provider = RecruitmentProvider::query()->create([
            'organization_id' => $organization->id,
            'slug' => $slug,
            'display_name' => $attributes['display_name']
                ?? $catalog['name']
                ?? ($adapter?->displayName() ?? $slug),
            'category' => $attributes['category']
                ?? $catalog['category']
                ?? ($adapter?->category() ?? 'job_board'),
            'status' => RecruitmentProvider::STATUS_DISCONNECTED,
            'external_account_id' => $attributes['external_account_id'] ?? null,
            'capabilities' => $attributes['capabilities']
                ?? $catalog['capabilities']
                ?? ($adapter?->capabilities() ?? []),
            'configuration' => $attributes['configuration'] ?? null,
            'metadata' => $attributes['metadata'] ?? null,
        ]);

        if ($actor) {
            $this->auditLogger->log($provider, 'recruitment_provider_registered', [
                'slug' => $provider->slug,
                'category' => $provider->category,
            ], $actor);
        }

        return $provider;
    }

    public function findProvider(Organization $organization, string $slug): ?RecruitmentProvider
    {
        return RecruitmentProvider::query()
            ->where('organization_id', $organization->id)
            ->where('slug', $slug)
            ->first();
    }

    /**
     * @return Collection<int, RecruitmentProvider>
     */
    public function listProviders(Organization $organization, ?string $category = null): Collection
    {
        $query = RecruitmentProvider::query()
            ->where('organization_id', $organization->id)
            ->with('credential')
            ->orderBy('category')
            ->orderBy('slug');

        if ($category) {
            $query->where('category', $category);
        }

        return $query->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function integrationCardsForOrganization(Organization $organization): array
    {
        $connections = $this->listProviders($organization)->keyBy('slug');
        $cards = [];

        foreach (config('recruitment.providers.catalog', []) as $slug => $entry) {
            /** @var RecruitmentProvider|null $connection */
            $connection = $connections->get($slug);
            $comingSoon = (bool) ($entry['coming_soon'] ?? false);
            $hasDriver = $this->registry->has($slug);

            $cards[] = [
                'slug' => $slug,
                'name' => $entry['name'] ?? $slug,
                'category' => $entry['category'] ?? null,
                'capabilities' => $entry['capabilities'] ?? [],
                'coming_soon' => $comingSoon || ! $hasDriver,
                'connected' => $connection?->isConnected() ?? false,
                'status' => $connection?->status ?? RecruitmentProvider::STATUS_DISCONNECTED,
                'last_synced_at' => $connection?->last_synced_at?->toIso8601String(),
                'last_error' => $connection?->last_error,
                'last_health_at' => $connection?->last_health_at?->toIso8601String(),
                'credential_expires_at' => $connection?->credential?->expires_at?->toIso8601String(),
                'provider_id' => $connection?->id,
            ];
        }

        return $cards;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function storeCredentials(
        RecruitmentProvider $provider,
        array $credentials,
        ?User $actor = null,
    ): RecruitmentProviderCredential {
        return DB::transaction(function () use ($provider, $credentials, $actor) {
            $payload = [
                'organization_id' => $provider->organization_id,
                'recruitment_provider_id' => $provider->id,
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

            $credential = RecruitmentProviderCredential::query()->updateOrCreate(
                ['recruitment_provider_id' => $provider->id],
                $payload,
            );

            if (array_key_exists('external_account_id', $credentials)) {
                $provider->external_account_id = $credentials['external_account_id'];
            }

            $credential->refresh();

            if ($credential->isExpired()) {
                $this->transitionStatus($provider, RecruitmentProvider::STATUS_EXPIRED, 'Credentials expired');
            } else {
                $this->transitionStatus($provider, RecruitmentProvider::STATUS_CONNECTED, null);
                $provider->connected_at = $provider->connected_at ?? now();
                $provider->disconnected_at = null;
                $provider->save();
            }

            if ($actor) {
                $this->auditLogger->log($provider, 'recruitment_provider_credentials_updated', [
                    'slug' => $provider->slug,
                    'status' => $provider->status,
                    'expires_at' => $credential->expires_at?->toIso8601String(),
                ], $actor);
            }

            return $credential;
        });
    }

    public function connect(Organization $organization, string $slug, ?User $actor = null): RecruitmentProvider
    {
        if (! $this->registry->has($slug) && ! (config("recruitment.providers.catalog.{$slug}"))) {
            throw new InvalidArgumentException("Unknown recruitment provider [{$slug}].");
        }

        if (! $this->registry->has($slug)) {
            throw new InvalidArgumentException("Recruitment provider [{$slug}] is not available yet.");
        }

        $provider = $this->registerProvider($organization, $slug, [], $actor);
        $adapter = $this->registry->resolve($slug);
        $result = $adapter->authorize($provider, ['phase' => 'start']);

        if (! empty($result['credentials'])) {
            $this->storeCredentials($provider, $result['credentials'], $actor);
        }

        $provider->refresh()->load('credential');

        if ($actor) {
            $this->auditLogger->log($provider, 'recruitment_provider_connected', [
                'slug' => $provider->slug,
                'status' => $provider->status,
            ], $actor);
        }

        return $provider;
    }

    public function disconnect(RecruitmentProvider $provider, ?User $actor = null): RecruitmentProvider
    {
        try {
            if ($this->registry->has($provider->slug)) {
                $this->registry->resolve($provider->slug)->revoke($provider);
            }
        } catch (Throwable) {
            // Best-effort revoke — local disconnect always proceeds.
        }

        return DB::transaction(function () use ($provider, $actor) {
            RecruitmentProviderCredential::query()
                ->where('recruitment_provider_id', $provider->id)
                ->delete();

            $this->transitionStatus($provider, RecruitmentProvider::STATUS_DISCONNECTED, null);
            $provider->disconnected_at = now();
            $provider->save();

            if ($actor) {
                $this->auditLogger->log($provider, 'recruitment_provider_disconnected', [
                    'slug' => $provider->slug,
                ], $actor);
            }

            return $provider->fresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function checkHealth(RecruitmentProvider $provider): array
    {
        if (! $this->registry->has($provider->slug)) {
            return [
                'healthy' => false,
                'status' => 'unavailable',
                'message' => 'Provider adapter is not registered.',
                'checked_at' => now()->toIso8601String(),
            ];
        }

        try {
            $result = $this->registry->resolve($provider->slug)->reportHealth($provider);
            $provider->last_health_at = now();

            if (! ($result['healthy'] ?? false) && $provider->isConnected()) {
                $provider->last_error = $result['message'] ?? 'Health check failed';
                $this->transitionStatus($provider, RecruitmentProvider::STATUS_ERROR, $provider->last_error);
            } elseif ($result['healthy'] ?? false) {
                $provider->last_error = null;
                if ($provider->status === RecruitmentProvider::STATUS_ERROR) {
                    $this->transitionStatus($provider, RecruitmentProvider::STATUS_CONNECTED, null);
                }
            }

            $provider->save();

            return $result;
        } catch (Throwable $e) {
            $provider->last_health_at = now();
            $provider->last_error = $e->getMessage();
            $this->transitionStatus($provider, RecruitmentProvider::STATUS_ERROR, $e->getMessage());
            $provider->save();

            return [
                'healthy' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
                'checked_at' => now()->toIso8601String(),
            ];
        }
    }

    public function transitionStatus(RecruitmentProvider $provider, string $status, ?string $error): void
    {
        RecruitmentProvider::assertValidStatus($status);
        $provider->status = $status;
        $provider->last_error = $error;
        $provider->save();
    }
}
