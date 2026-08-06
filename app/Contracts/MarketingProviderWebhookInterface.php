<?php

namespace App\Contracts;

/**
 * Optional capability: inbound webhook verification and payload normalization (P7C.6).
 *
 * Kept separate from MarketingProviderInterface so the P7C.1 contract stays frozen.
 * Adapters validate signatures and return normalized DTOs only — no Eloquent writes,
 * no CRM lead creation, no Marketing Platform writes.
 * MarketingProviderService is the sole write authority for webhook event persistence.
 */
interface MarketingProviderWebhookInterface
{
    /**
     * Handle provider webhook subscription verification (e.g. Meta hub.challenge).
     *
     * @param  array<string, mixed>  $query
     * @return array{
     *     ok: bool,
     *     challenge?: string|null,
     *     message?: string|null
     * }
     */
    public function verifyWebhook(array $query): array;

    /**
     * Validate signature against the raw request body and normalize the payload.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     * @return array{
     *     ok: bool,
     *     event?: string|null,
     *     normalized?: array<string, mixed>|null,
     *     delivery_id?: string|null,
     *     signature?: string|null,
     *     message?: string|null,
     *     http_status?: int|null
     * }
     */
    public function validateAndNormalizeWebhook(string $rawBody, array $payload, array $headers = []): array;
}
