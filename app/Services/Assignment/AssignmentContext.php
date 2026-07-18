<?php

namespace App\Services\Assignment;

/**
 * Immutable input for assignment resolution. Entity-agnostic.
 *
 * @phpstan-type AttributeBag array{
 *     source?: string|null,
 *     status?: string|null,
 *     country?: string|null,
 *     lead_type?: string|null,
 *     pipeline?: string|null,
 *     metadata?: array<string, mixed>
 * }
 */
final class AssignmentContext
{
    /**
     * @param  array{
     *     source?: string|null,
     *     status?: string|null,
     *     country?: string|null,
     *     lead_type?: string|null,
     *     pipeline?: string|null,
     *     metadata?: array<string, mixed>
     * }  $attributes
     */
    public function __construct(
        public readonly int $organizationId,
        public readonly string $entityType,
        public readonly array $attributes = [],
    ) {}

    /**
     * @param  array<string, mixed>  $leadData
     */
    public static function forLead(int $organizationId, array $leadData = []): self
    {
        $metadata = [];

        if (isset($leadData['custom_fields']) && is_array($leadData['custom_fields'])) {
            $metadata = $leadData['custom_fields'];
        }

        if (isset($leadData['metadata']) && is_array($leadData['metadata'])) {
            $metadata = array_merge($metadata, $leadData['metadata']);
        }

        return new self(
            organizationId: $organizationId,
            entityType: 'lead',
            attributes: [
                'source' => $leadData['source'] ?? null,
                'status' => $leadData['status'] ?? null,
                'country' => $leadData['country'] ?? ($metadata['country'] ?? null),
                'lead_type' => $leadData['lead_type'] ?? ($metadata['lead_type'] ?? null),
                'pipeline' => $leadData['pipeline'] ?? ($metadata['pipeline'] ?? null),
                'metadata' => $metadata,
            ],
        );
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
