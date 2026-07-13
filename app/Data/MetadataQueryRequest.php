<?php

namespace App\Data;

use InvalidArgumentException;

class MetadataQueryRequest
{
    /**
     * @param  array<int, MetadataQueryFilter>  $filters
     * @param  array<string, mixed>|null  $search
     */
    public function __construct(
        public readonly string $entityType,
        public readonly array $filters = [],
        public readonly ?MetadataQuerySort $sort = null,
        public readonly ?array $search = null,
        public readonly ?int $page = null,
        public readonly ?int $perPage = null,
        public readonly string $context = 'web_index',
        public readonly ?int $organizationId = null,
    ) {
        foreach ($this->filters as $filter) {
            if (! $filter instanceof MetadataQueryFilter) {
                throw new InvalidArgumentException('Metadata query filters must be MetadataQueryFilter instances.');
            }
        }
    }
}
