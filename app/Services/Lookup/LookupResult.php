<?php

namespace App\Services\Lookup;

/**
 * Standard lookup result item.
 *
 * @phpstan-type LookupResultArray array{
 *     id: int|string,
 *     label: string,
 *     subtitle: string|null,
 *     badge: string|null,
 *     metadata: array<string, mixed>
 * }
 */
class LookupResult
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly int|string $id,
        public readonly string $label,
        public readonly ?string $subtitle = null,
        public readonly ?string $badge = null,
        public readonly array $metadata = [],
    ) {}

    /**
     * @return LookupResultArray
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'subtitle' => $this->subtitle,
            'badge' => $this->badge,
            'metadata' => $this->metadata,
        ];
    }
}
