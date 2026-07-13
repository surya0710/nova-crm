<?php

namespace App\Services;

use App\Data\MetadataQueryFilter;
use App\Data\MetadataQueryRequest;
use App\Data\MetadataQuerySort;
use App\Models\MetadataFieldDefinition;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class MetadataQueryDefinitionService
{
    protected array $capabilityColumns = [
        'filter' => 'is_filterable',
        'sort' => 'is_sortable',
        'search' => 'is_searchable',
        'report' => 'is_reportable',
        'api' => 'is_api_visible',
        'export' => 'is_exportable',
    ];

    /** @var array<string, Collection<string, MetadataFieldDefinition>> */
    protected array $cache = [];

    /**
     * @return Collection<string, MetadataFieldDefinition>
     */
    public function activeDefinitions(int $organizationId, string $entityType): Collection
    {
        return $this->definitionsFor($organizationId, $entityType);
    }

    /**
     * @return Collection<string, MetadataFieldDefinition>
     */
    public function definitionsFor(int $organizationId, string $entityType, ?string $capability = null): Collection
    {
        if ($capability !== null && ! array_key_exists($capability, $this->capabilityColumns)) {
            throw new InvalidArgumentException("Unsupported metadata query capability [{$capability}].");
        }

        $cacheKey = implode(':', [$organizationId, $entityType, $capability ?? 'active']);

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $query = MetadataFieldDefinition::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('entity_type', $entityType)
            ->where('status', 'active');

        if ($capability !== null) {
            $query->where($this->capabilityColumns[$capability], true);
        }

        return $this->cache[$cacheKey] = $query
            ->get()
            ->keyBy('key');
    }

    public function definitionFor(int $organizationId, string $entityType, string $key, ?string $capability = null): ?MetadataFieldDefinition
    {
        return $this->definitionsFor($organizationId, $entityType, $capability)->get($key);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function requestForWebIndex(int $organizationId, string $entityType, array $input): MetadataQueryRequest
    {
        return new MetadataQueryRequest(
            entityType: $entityType,
            filters: $this->filtersFromInput($organizationId, $entityType, $input['metadata_filters'] ?? [], 'web_index'),
            sort: $this->sortFromInput($organizationId, $entityType, $input, 'web_index'),
            context: 'web_index',
            organizationId: $organizationId,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function requestForApi(int $organizationId, string $entityType, array $input): MetadataQueryRequest
    {
        return new MetadataQueryRequest(
            entityType: $entityType,
            filters: $this->filtersFromInput($organizationId, $entityType, $input['metadata_filters'] ?? [], 'api'),
            sort: $this->sortFromInput($organizationId, $entityType, $input, 'api'),
            context: 'api',
            organizationId: $organizationId,
            page: isset($input['page']) ? (int) $input['page'] : null,
            perPage: isset($input['per_page']) ? (int) $input['per_page'] : null,
        );
    }

    /**
     * @return array{filterable: Collection<string, MetadataFieldDefinition>, sortable: Collection<string, MetadataFieldDefinition>}
     */
    public function apiFields(int $organizationId, string $entityType): array
    {
        $apiVisible = $this->definitionsFor($organizationId, $entityType, 'api')
            ->reject(fn (MetadataFieldDefinition $definition) => $definition->is_sensitive);

        return [
            'filterable' => $this->definitionsFor($organizationId, $entityType, 'filter')
                ->intersectByKeys($apiVisible),
            'sortable' => $this->definitionsFor($organizationId, $entityType, 'sort')
                ->intersectByKeys($apiVisible),
        ];
    }

    /**
     * @return array{filterable: Collection<string, MetadataFieldDefinition>, sortable: Collection<string, MetadataFieldDefinition>}
     */
    public function webIndexFields(int $organizationId, string $entityType): array
    {
        return [
            'filterable' => $this->definitionsFor($organizationId, $entityType, 'filter')
                ->reject(fn (MetadataFieldDefinition $definition) => $definition->is_sensitive),
            'sortable' => $this->definitionsFor($organizationId, $entityType, 'sort')
                ->reject(fn (MetadataFieldDefinition $definition) => $definition->is_sensitive),
        ];
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * @param  mixed  $filters
     * @return array<int, MetadataQueryFilter>
     */
    protected function filtersFromInput(int $organizationId, string $entityType, mixed $filters, string $context = 'web_index'): array
    {
        $compiled = [];

        foreach ((array) $filters as $index => $filter) {
            if (! is_array($filter)) {
                if ($context === 'api') {
                    throw ValidationException::withMessages([
                        "metadata_filters.{$index}" => __('Each metadata filter must be an object.'),
                    ]);
                }

                continue;
            }

            $key = trim((string) ($filter['key'] ?? ''));
            $operator = trim((string) ($filter['operator'] ?? ''));

            if ($key === '' || $operator === '') {
                if ($context === 'api' && ($key !== '' || $operator !== '' || array_key_exists('value', $filter))) {
                    throw ValidationException::withMessages([
                        "metadata_filters.{$index}" => __('Metadata filters require both key and operator.'),
                    ]);
                }

                continue;
            }

            $definition = $this->definitionFor($organizationId, $entityType, $key);

            if (! $definition) {
                if ($context === 'api') {
                    throw ValidationException::withMessages([
                        "metadata_filters.{$index}.key" => __('This metadata field is not active or does not exist.'),
                    ]);
                }

                continue;
            }

            if ($definition->is_sensitive) {
                throw ValidationException::withMessages([
                    "metadata_filters.{$index}.key" => __('Sensitive metadata fields cannot be used for filtering.'),
                ]);
            }

            if (! $definition->is_filterable) {
                throw ValidationException::withMessages([
                    "metadata_filters.{$index}.key" => __('This metadata field is not filterable.'),
                ]);
            }

            if ($context === 'api' && ! $definition->is_api_visible) {
                throw ValidationException::withMessages([
                    "metadata_filters.{$index}.key" => __('This metadata field is not API visible.'),
                ]);
            }

            $compiled[] = new MetadataQueryFilter($key, $operator, $filter['value'] ?? null);
        }

        return $compiled;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function sortFromInput(int $organizationId, string $entityType, array $input, string $context = 'web_index'): ?MetadataQuerySort
    {
        $sortInput = $input['metadata_sort'] ?? [];
        $key = '';
        $direction = 'asc';

        if (is_array($sortInput)) {
            $key = trim((string) ($sortInput['key'] ?? ''));
            $direction = trim((string) ($sortInput['direction'] ?? 'asc')) ?: 'asc';
        }

        if ($key === '') {
            $key = trim((string) ($input['metadata_sort_key'] ?? ''));
            $direction = trim((string) ($input['metadata_sort_direction'] ?? $direction)) ?: 'asc';
        }

        if ($key === '') {
            return null;
        }

        $definition = $this->definitionFor($organizationId, $entityType, $key);

        if (! $definition) {
            if ($context === 'api') {
                throw ValidationException::withMessages([
                    'metadata_sort.key' => __('This metadata field is not active or does not exist.'),
                ]);
            }

            return null;
        }

        if ($definition->is_sensitive) {
            throw ValidationException::withMessages([
                'metadata_sort.key' => __('Sensitive metadata fields cannot be used for sorting.'),
            ]);
        }

        if (! $definition->is_sortable) {
            throw ValidationException::withMessages([
                'metadata_sort.key' => __('This metadata field is not sortable.'),
            ]);
        }

        if ($context === 'api' && ! $definition->is_api_visible) {
            throw ValidationException::withMessages([
                'metadata_sort.key' => __('This metadata field is not API visible.'),
            ]);
        }

        return new MetadataQuerySort($key, $direction);
    }
}
