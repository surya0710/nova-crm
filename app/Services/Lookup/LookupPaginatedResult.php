<?php

namespace App\Services\Lookup;

class LookupPaginatedResult
{
    /**
     * @param  list<LookupResult>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $total,
        public readonly bool $hasMore,
    ) {}

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int|bool>}
     */
    public function toArray(): array
    {
        return [
            'data' => array_map(static fn (LookupResult $item) => $item->toArray(), $this->items),
            'meta' => [
                'page' => $this->page,
                'per_page' => $this->perPage,
                'total' => $this->total,
                'has_more' => $this->hasMore,
            ],
        ];
    }
}
