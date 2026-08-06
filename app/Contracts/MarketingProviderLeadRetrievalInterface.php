<?php

namespace App\Contracts;

use App\Models\MarketingProvider;

/**
 * Optional capability: retrieve a single provider lead entry by external id (P7C.7).
 *
 * Used by webhook processing to fetch complete lead details after a notification.
 * Kept separate from MarketingProviderInterface so the P7C.1 contract stays frozen.
 * Adapters return a normalized DTO only — no Eloquent writes, no CRM lead creation.
 * MarketingProviderService orchestrates persistence via the shared import pipeline.
 */
interface MarketingProviderLeadRetrievalInterface
{
    /**
     * Fetch a single lead entry by its external (provider) id.
     *
     * @param  array<string, mixed>  $context  Optional hints (e.g. form_id, page_id)
     * @return array{
     *     ok: bool,
     *     entry: array{
     *         external_lead_id: string,
     *         external_form_id: string|null,
     *         external_page_id?: string|null,
     *         created_time?: string|null,
     *         fields: array<string, string|null>,
     *         unmapped_fields: array<string, mixed>,
     *         raw?: array<string, mixed>,
     *         fetch_ok: bool,
     *         error?: string|null
     *     }|null,
     *     status?: string|null,
     *     missing?: bool,
     *     message?: string|null
     * }
     */
    public function retrieveLeadEntry(MarketingProvider $provider, string $leadId, array $context = []): array;
}
