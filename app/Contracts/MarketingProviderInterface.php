<?php

namespace App\Contracts;

use App\Models\MarketingProvider;

/**
 * Provider-agnostic integration contract (P7C.1).
 *
 * Concrete adapters (Meta, Google Ads, LinkedIn, …) implement this interface.
 * They translate provider APIs into normalized results. Persistence of provider
 * rows and credentials belongs exclusively to MarketingProviderService.
 *
 * Adapters must never write marketing_visitors, sessions, touches, attributions,
 * or conversions — those remain Marketing Platform write authorities.
 */
interface MarketingProviderInterface
{
    /**
     * Stable provider slug (e.g. meta, google_ads, linkedin).
     */
    public function slug(): string;

    /**
     * Human-readable provider name for UI/catalog.
     */
    public function displayName(): string;

    /**
     * Declared capabilities. Unknown capabilities must not be assumed.
     *
     * @return list<string> e.g. oauth, sync, webhooks, offline_conversions, audiences
     */
    public function capabilities(): array;

    /**
     * Begin or complete authorization (OAuth or equivalent).
     * Implementations return normalized context; the service persists credentials.
     *
     * @param  array<string, mixed>  $context
     * @return array{authorization_url?: string|null, credentials?: array<string, mixed>|null, status?: string|null, metadata?: array<string, mixed>}
     */
    public function authorize(MarketingProvider $provider, array $context = []): array;

    /**
     * Refresh expired or soon-to-expire credentials via the provider API.
     * Implementations return updated credential fields; the service persists them.
     *
     * @return array{access_token?: string|null, refresh_token?: string|null, expires_at?: string|null, token_type?: string|null, scopes?: list<string>|null, metadata?: array<string, mixed>}
     */
    public function refreshCredentials(MarketingProvider $provider): array;

    /**
     * Revoke access at the provider (best-effort). Local disconnect is owned by the service.
     */
    public function revoke(MarketingProvider $provider): void;

    /**
     * Pull provider data into normalized DTOs. Platform sync persistence is future work;
     * this method must not write Eloquent models itself.
     *
     * @param  array<string, mixed>  $options
     * @return array{ok: bool, accounts?: list<array<string, mixed>>, campaigns?: list<array<string, mixed>>, message?: string|null, metadata?: array<string, mixed>}
     */
    public function synchronize(MarketingProvider $provider, array $options = []): array;

    /**
     * Validate and normalize an inbound webhook payload.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     * @return array{ok: bool, event?: string|null, normalized?: array<string, mixed>|null, message?: string|null}
     */
    public function receiveWebhook(MarketingProvider $provider, array $payload, array $headers = []): array;

    /**
     * Push offline conversions sourced from MarketingConversion rows (platform builds payload).
     *
     * @param  list<array<string, mixed>>  $conversions
     * @return array{ok: bool, uploaded: int, failed: int, message?: string|null, results?: list<array<string, mixed>>}
     */
    public function uploadConversions(MarketingProvider $provider, array $conversions): array;

    /**
     * Report current provider-side health for the connected account.
     *
     * @return array{healthy: bool, status?: string|null, message?: string|null, checked_at?: string|null, metadata?: array<string, mixed>}
     */
    public function reportHealth(MarketingProvider $provider): array;
}
