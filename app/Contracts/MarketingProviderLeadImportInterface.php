<?php

namespace App\Contracts;

use App\Models\MarketingProvider;

/**
 * Optional capability: fetch provider lead submissions for manual import (P7C.5).
 *
 * Kept separate from MarketingProviderInterface so the P7C.1 contract stays frozen.
 * Adapters return normalized DTOs only — no Eloquent writes, no webhooks, no polling.
 * MarketingProviderService orchestrates LeadService creation and dedup persistence.
 */
interface MarketingProviderLeadImportInterface
{
    /**
     * Fetch lead entries for selected/synchronized forms.
     *
     * @param  array<string, mixed>  $options
     * @return array{
     *     ok: bool,
     *     entries: list<array{
     *         external_lead_id: string,
     *         external_form_id: string|null,
     *         external_page_id?: string|null,
     *         created_time?: string|null,
     *         fields: array<string, string|null>,
     *         unmapped_fields: array<string, mixed>,
     *         raw?: array<string, mixed>,
     *         fetch_ok: bool,
     *         error?: string|null
     *     }>,
     *     fetched: int,
     *     failed: int,
     *     message?: string|null,
     *     status?: string|null,
     *     fetched_at?: string|null
     * }
     */
    public function importLeadEntries(MarketingProvider $provider, array $options = []): array;
}
