<?php

namespace App\Contracts;

use App\Models\MarketingProvider;

/**
 * Optional capability for providers that expose selectable business assets (P7C.3).
 *
 * Kept separate from MarketingProviderInterface so the P7C.1 contract stays frozen.
 * Adapters normalize Graph/API payloads; MarketingProviderService alone persists
 * selections into MarketingProviderCredential.configuration.
 */
interface MarketingProviderAssetDiscoveryInterface
{
    /**
     * Discover assets available to the connected account (read-only).
     *
     * @param  array<string, mixed>  $options  e.g. business_id, page_id filters
     * @return array{
     *     ok: bool,
     *     assets: array{
     *         businesses: list<array{id: string, name: string|null}>,
     *         ad_accounts: list<array{id: string, name: string|null, business_id?: string|null}>,
     *         pages: list<array{id: string, name: string|null, business_id?: string|null}>,
     *         pixels: list<array{id: string, name: string|null, business_id?: string|null}>,
     *         lead_forms: list<array{id: string, name: string|null, page_id?: string|null}>
     *     },
     *     message?: string|null,
     *     discovered_at?: string|null,
     *     status?: string|null
     * }
     */
    public function discoverAssets(MarketingProvider $provider, array $options = []): array;

    /**
     * Verify client-submitted selections against live provider assets.
     * Returns a sanitized configuration payload ready for persistence.
     *
     * @param  array<string, mixed>  $selection
     * @return array{
     *     business_id: string|null,
     *     ad_account_id: string|null,
     *     page_id: string|null,
     *     pixel_id: string|null,
     *     lead_form_ids: list<string>
     * }
     */
    public function validateAssetSelection(MarketingProvider $provider, array $selection): array;
}
