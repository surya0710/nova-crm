<?php

namespace App\Services;

use App\Models\MetadataFieldDefinition;

class MetadataApiPresenter
{
    public function __construct(
        protected MetadataQueryDefinitionService $definitions,
    ) {}

    /**
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>
     */
    public function customFieldsFor(int $organizationId, string $entityType, ?array $values): array
    {
        if (! is_array($values) || $values === []) {
            return [];
        }

        $visibleKeys = $this->definitions
            ->definitionsFor($organizationId, $entityType, 'api')
            ->reject(fn (MetadataFieldDefinition $definition) => $definition->is_sensitive)
            ->keys()
            ->all();

        return array_intersect_key($values, array_flip($visibleKeys));
    }
}
