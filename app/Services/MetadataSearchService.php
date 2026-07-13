<?php

namespace App\Services;

use App\Data\MetadataQueryRequest;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\Opportunity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class MetadataSearchService
{
    protected array $entityModels = [
        'lead' => Lead::class,
        'customer' => Customer::class,
        'opportunity' => Opportunity::class,
    ];

    public function __construct(
        protected MetadataQueryDefinitionService $definitions,
        protected MetadataQueryService $queries,
        protected ProjectionSearchProvider $provider,
        protected TenantContext $tenantContext,
    ) {}

    /**
     * Add metadata search as an OR branch on an existing entity query.
     */
    public function applySearchConstraint(
        Builder $builder,
        string $entityType,
        string $term,
        ?int $organizationId = null,
        string $mode = 'contains'
    ): void {
        $organizationId = $this->resolveOrganizationId($organizationId);
        $normalizedTerm = $this->normalizeTerm($term);

        if ($normalizedTerm === '') {
            return;
        }

        $searchable = $this->searchableDefinitions($organizationId, $entityType);

        if ($searchable->isEmpty()) {
            return;
        }

        $builder->orWhere(function ($metadataBuilder) use ($organizationId, $entityType, $searchable, $normalizedTerm, $mode) {
            $this->provider->apply($metadataBuilder, $organizationId, $entityType, $searchable, $normalizedTerm, $mode);
        });
    }

    /**
     * @return array<int, int>
     */
    public function matchingEntityIds(
        string $entityType,
        string $term,
        ?int $organizationId = null,
        string $mode = 'contains'
    ): array {
        $organizationId = $this->resolveOrganizationId($organizationId);
        $normalizedTerm = $this->normalizeTerm($term);

        if ($normalizedTerm === '') {
            return [];
        }

        $searchable = $this->searchableDefinitions($organizationId, $entityType);

        if ($searchable->isEmpty()) {
            return [];
        }

        $builder = $this->entityBuilder($entityType);

        $this->queries->apply($builder, new MetadataQueryRequest(
            entityType: $entityType,
            context: 'global_search',
            organizationId: $organizationId,
        ), $organizationId);

        $this->provider->apply($builder, $organizationId, $entityType, $searchable, $normalizedTerm, $mode);

        return $builder->pluck($builder->getModel()->getTable().'.id')->all();
    }

    public function hasSearchableFields(string $entityType, ?int $organizationId = null): bool
    {
        $organizationId = $this->resolveOrganizationId($organizationId);

        return $this->searchableDefinitions($organizationId, $entityType)->isNotEmpty();
    }

    /**
     * @return Collection<string, MetadataFieldDefinition>
     */
    public function searchableDefinitions(int $organizationId, string $entityType): Collection
    {
        $this->assertSupportedEntityType($entityType);

        return $this->definitions
            ->definitionsFor($organizationId, $entityType, 'search')
            ->reject(fn (MetadataFieldDefinition $definition) => $definition->is_sensitive);
    }

    public function normalizeTerm(string $term): string
    {
        return strtolower(trim($term));
    }

    protected function entityBuilder(string $entityType): Builder
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $this->entityModels[$entityType];

        return $modelClass::query();
    }

    protected function resolveOrganizationId(?int $organizationId): int
    {
        $resolved = $organizationId ?? $this->tenantContext->id();

        if (! $resolved) {
            throw new InvalidArgumentException('Metadata search requires an organization context.');
        }

        return (int) $resolved;
    }

    protected function assertSupportedEntityType(string $entityType): void
    {
        if (! array_key_exists($entityType, $this->entityModels)) {
            throw new InvalidArgumentException("Metadata search is not supported for entity type [{$entityType}].");
        }
    }
}
