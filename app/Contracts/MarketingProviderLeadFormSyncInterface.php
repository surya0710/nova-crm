<?php

namespace App\Contracts;

use App\Models\MarketingProvider;

/**
 * Optional capability: synchronize lead-form metadata catalogs (P7C.4).
 *
 * Kept separate from MarketingProviderInterface so the P7C.1 contract stays frozen.
 * Adapters return normalized DTOs only; MarketingProviderService persists catalog rows.
 * Never imports lead submissions or registers webhooks.
 */
interface MarketingProviderLeadFormSyncInterface
{
    /**
     * Fetch metadata for selected lead forms (read-only at the provider API).
     *
     * @param  array<string, mixed>  $options
     * @return array{
     *     ok: bool,
     *     forms: list<array{
     *         external_form_id: string,
     *         external_page_id?: string|null,
     *         name?: string|null,
     *         provider_status?: string|null,
     *         locale?: string|null,
     *         questions?: list<array<string, mixed>>,
     *         raw_metadata?: array<string, mixed>,
     *         external_updated_at?: string|null,
     *         sync_ok: bool,
     *         missing?: bool,
     *         error?: string|null
     *     }>,
     *     synced: int,
     *     failed: int,
     *     message?: string|null,
     *     status?: string|null,
     *     synced_at?: string|null
     * }
     */
    public function synchronizeLeadForms(MarketingProvider $provider, array $options = []): array;
}
