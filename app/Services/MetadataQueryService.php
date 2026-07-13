<?php

namespace App\Services;

use App\Data\MetadataQueryFilter;
use App\Data\MetadataQueryRequest;
use App\Data\MetadataQuerySort;
use App\Models\MetadataFieldDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class MetadataQueryService
{
    protected array $operatorsByType = [
        'text' => ['equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'ends_with', 'empty', 'not_empty', 'is_empty', 'is_not_empty'],
        'textarea' => ['equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'ends_with', 'empty', 'not_empty', 'is_empty', 'is_not_empty'],
        'email' => ['equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'ends_with', 'empty', 'not_empty', 'is_empty', 'is_not_empty'],
        'url' => ['equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'ends_with', 'empty', 'not_empty', 'is_empty', 'is_not_empty'],
        'phone' => ['equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'ends_with', 'empty', 'not_empty', 'is_empty', 'is_not_empty'],
        'select' => ['equals', 'not_equals', 'in', 'not_in', 'empty', 'not_empty', 'is_empty', 'is_not_empty'],
        'radio' => ['equals', 'not_equals', 'in', 'not_in', 'empty', 'not_empty', 'is_empty', 'is_not_empty'],
        'multi_select' => ['contains_any', 'contains_all', 'contains_none', 'empty', 'not_empty', 'is_empty', 'is_not_empty'],
        'number' => ['equals', 'not_equals', 'greater_than', 'greater_than_or_equal', 'less_than', 'less_than_or_equal', 'gt', 'gte', 'lt', 'lte', 'between', 'empty', 'not_empty', 'is_empty', 'is_not_empty'],
        'decimal' => ['equals', 'not_equals', 'greater_than', 'greater_than_or_equal', 'less_than', 'less_than_or_equal', 'gt', 'gte', 'lt', 'lte', 'between', 'empty', 'not_empty', 'is_empty', 'is_not_empty'],
        'currency' => ['equals', 'not_equals', 'greater_than', 'greater_than_or_equal', 'less_than', 'less_than_or_equal', 'gt', 'gte', 'lt', 'lte', 'between', 'empty', 'not_empty', 'is_empty', 'is_not_empty'],
        'percentage' => ['equals', 'not_equals', 'greater_than', 'greater_than_or_equal', 'less_than', 'less_than_or_equal', 'gt', 'gte', 'lt', 'lte', 'between', 'empty', 'not_empty', 'is_empty', 'is_not_empty'],
        'user' => ['equals', 'not_equals', 'greater_than', 'greater_than_or_equal', 'less_than', 'less_than_or_equal', 'gt', 'gte', 'lt', 'lte', 'between', 'empty', 'not_empty', 'is_empty', 'is_not_empty'],
        'team' => ['equals', 'not_equals', 'greater_than', 'greater_than_or_equal', 'less_than', 'less_than_or_equal', 'gt', 'gte', 'lt', 'lte', 'between', 'empty', 'not_empty', 'is_empty', 'is_not_empty'],
        'date' => ['equals', 'before', 'after', 'between', 'empty', 'not_empty', 'is_empty', 'is_not_empty'],
        'datetime' => ['equals', 'before', 'after', 'between', 'empty', 'not_empty', 'is_empty', 'is_not_empty'],
        'time' => ['equals', 'before', 'after', 'between', 'empty', 'not_empty', 'is_empty', 'is_not_empty'],
        'boolean' => ['true', 'false', 'is_true', 'is_false', 'empty', 'not_empty', 'is_empty', 'is_not_empty'],
    ];

    protected array $sortableTypes = [
        'text',
        'textarea',
        'email',
        'url',
        'phone',
        'select',
        'radio',
        'number',
        'decimal',
        'currency',
        'percentage',
        'user',
        'team',
        'boolean',
        'date',
        'datetime',
        'time',
    ];

    public function __construct(
        protected MetadataQueryDefinitionService $definitions,
        protected TenantContext $tenantContext,
    ) {}

    public function apply(Builder $builder, MetadataQueryRequest $request, ?int $organizationId = null): Builder
    {
        $organizationId = $this->resolveOrganizationId($request, $organizationId);
        $this->applyEntityTenantConstraint($builder, $request->entityType, $organizationId);
        $this->validateSearch($request, $organizationId);

        foreach ($request->filters as $filter) {
            $definition = $this->definitionForFilter($organizationId, $request, $filter);
            $this->assertOperatorSupported($definition, $filter->operator);
            $this->applyFilter($builder, $organizationId, $request->entityType, $definition, $filter);
        }

        if ($request->sort) {
            $definition = $this->definitionForSort($organizationId, $request, $request->sort);
            $this->applySort($builder, $organizationId, $request->entityType, $definition, $request->sort);
        }

        return $builder;
    }

    public function compile(Builder $builder, MetadataQueryRequest $request, ?int $organizationId = null): Builder
    {
        return $this->apply($builder, $request, $organizationId);
    }

    public function applyForWebIndex(Builder $builder, MetadataQueryRequest $request, ?int $organizationId = null): Builder
    {
        try {
            return $this->apply($builder, $request, $organizationId);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'metadata_filters' => $exception->getMessage(),
            ]);
        }
    }

    public function applyForApi(Builder $builder, MetadataQueryRequest $request, ?int $organizationId = null): Builder
    {
        try {
            return $this->apply($builder, $request, $organizationId);
        } catch (InvalidArgumentException $exception) {
            $field = str_contains($exception->getMessage(), 'sort') ? 'metadata_sort.key' : 'metadata_filters';

            throw ValidationException::withMessages([
                $field => $exception->getMessage(),
            ]);
        }
    }

    protected function definitionForFilter(int $organizationId, MetadataQueryRequest $request, MetadataQueryFilter $filter): MetadataFieldDefinition
    {
        $definition = $this->definitions->definitionFor($organizationId, $request->entityType, $filter->key);

        if (! $definition) {
            throw new InvalidArgumentException("Metadata field [{$filter->key}] is not active or does not exist for [{$request->entityType}].");
        }

        if (! $definition->is_filterable) {
            throw new InvalidArgumentException("Metadata field [{$filter->key}] is not filterable.");
        }

        $this->assertContextCapabilities($definition, $request->context);

        return $definition;
    }

    protected function definitionForSort(int $organizationId, MetadataQueryRequest $request, MetadataQuerySort $sort): MetadataFieldDefinition
    {
        $definition = $this->definitions->definitionFor($organizationId, $request->entityType, $sort->key);

        if (! $definition) {
            throw new InvalidArgumentException("Metadata field [{$sort->key}] is not active or does not exist for [{$request->entityType}].");
        }

        if (! $definition->is_sortable) {
            throw new InvalidArgumentException("Metadata field [{$sort->key}] is not sortable.");
        }

        if (! in_array($definition->type, $this->sortableTypes, true)) {
            throw new InvalidArgumentException("Metadata field [{$sort->key}] type [{$definition->type}] is not sortable.");
        }

        $this->assertContextCapabilities($definition, $request->context);

        return $definition;
    }

    protected function validateSearch(MetadataQueryRequest $request, int $organizationId): void
    {
        if (! $request->search) {
            return;
        }

        $keys = $request->search['keys'] ?? [];

        if ($keys === []) {
            return;
        }

        foreach ((array) $keys as $key) {
            $definition = $this->definitions->definitionFor($organizationId, $request->entityType, (string) $key);

            if (! $definition) {
                throw new InvalidArgumentException("Metadata search field [{$key}] is not active or does not exist for [{$request->entityType}].");
            }

            if (! $definition->is_searchable) {
                throw new InvalidArgumentException("Metadata field [{$key}] is not searchable.");
            }

            $this->assertContextCapabilities($definition, $request->context);
        }
    }

    protected function assertContextCapabilities(MetadataFieldDefinition $definition, string $context): void
    {
        if ($context === 'report' && ! $definition->is_reportable) {
            throw new InvalidArgumentException("Metadata field [{$definition->key}] is not reportable.");
        }

        if ($context === 'api' && ! $definition->is_api_visible) {
            throw new InvalidArgumentException("Metadata field [{$definition->key}] is not API visible.");
        }
    }

    protected function assertOperatorSupported(MetadataFieldDefinition $definition, string $operator): void
    {
        $operators = $this->operatorsByType[$definition->type] ?? [];

        if (! in_array($operator, $operators, true)) {
            throw new InvalidArgumentException("Operator [{$operator}] is not supported for metadata field [{$definition->key}] of type [{$definition->type}].");
        }
    }

    protected function applyFilter(Builder $builder, int $organizationId, string $entityType, MetadataFieldDefinition $definition, MetadataQueryFilter $filter): void
    {
        $operator = $this->canonicalOperator($filter->operator);

        if ($operator === 'empty') {
            $this->whereProjectionMissing($builder, $organizationId, $entityType, $definition);

            return;
        }

        if ($operator === 'not_empty') {
            $this->whereProjectionExists($builder, $organizationId, $entityType, $definition);

            return;
        }

        if ($definition->type === 'multi_select') {
            $this->applyMultiSelectFilter($builder, $organizationId, $entityType, $definition, $operator, $filter->value);

            return;
        }

        $column = $this->valueColumnFor($definition);
        $value = $this->normalizeValue($definition, $filter->value);

        $this->whereProjectionExists($builder, $organizationId, $entityType, $definition, function ($query) use ($operator, $column, $value, $definition) {
            if ($definition->type !== 'multi_select') {
                $query->where('value_hash', 'scalar');
            }

            match ($operator) {
                'equals' => $query->where($column, '=', $value),
                'not_equals' => $query->where($column, '!=', $value),
                'contains' => $query->where($column, 'like', '%'.$this->escapeLike($value).'%'),
                'not_contains' => $query->where($column, 'not like', '%'.$this->escapeLike($value).'%'),
                'starts_with' => $query->where($column, 'like', $this->escapeLike($value).'%'),
                'ends_with' => $query->where($column, 'like', '%'.$this->escapeLike($value)),
                'greater_than' => $query->where($column, '>', $value),
                'greater_than_or_equal' => $query->where($column, '>=', $value),
                'less_than' => $query->where($column, '<', $value),
                'less_than_or_equal' => $query->where($column, '<=', $value),
                'before' => $query->where($column, '<', $value),
                'after' => $query->where($column, '>', $value),
                'between' => $query->whereBetween($column, $this->normalizeBetweenValues($value)),
                'true' => $query->where($column, true),
                'false' => $query->where($column, false),
                'in' => $query->whereIn($column, $this->normalizeArrayValue($value)),
                'not_in' => $query->whereNotIn($column, $this->normalizeArrayValue($value)),
                default => throw new InvalidArgumentException("Unsupported metadata operator [{$operator}]."),
            };
        });
    }

    protected function applyMultiSelectFilter(
        Builder $builder,
        int $organizationId,
        string $entityType,
        MetadataFieldDefinition $definition,
        string $operator,
        mixed $value
    ): void {
        $values = $this->normalizeArrayValue($this->normalizeValue($definition, $value));

        match ($operator) {
            'contains_any' => $this->whereProjectionExists(
                $builder,
                $organizationId,
                $entityType,
                $definition,
                fn ($query) => $query->whereIn('value_string', $values)
            ),
            'contains_all' => collect($values)->each(fn ($item) => $this->whereProjectionExists(
                $builder,
                $organizationId,
                $entityType,
                $definition,
                fn ($query) => $query->where('value_string', $item)
            )),
            'contains_none' => $this->whereProjectionMissing(
                $builder,
                $organizationId,
                $entityType,
                $definition,
                fn ($query) => $query->whereIn('value_string', $values)
            ),
            default => throw new InvalidArgumentException("Unsupported multi-select metadata operator [{$operator}]."),
        };
    }

    protected function applySort(Builder $builder, int $organizationId, string $entityType, MetadataFieldDefinition $definition, MetadataQuerySort $sort): void
    {
        $entityTable = $builder->getModel()->getTable();
        $alias = 'metadata_sort_'.$definition->id;
        $column = $this->valueColumnFor($definition);

        $builder
            ->select($entityTable.'.*')
            ->leftJoin('metadata_value_projections as '.$alias, function ($join) use ($alias, $entityTable, $organizationId, $entityType, $definition) {
                $join->on($alias.'.entity_id', '=', $entityTable.'.id')
                    ->where($alias.'.organization_id', '=', $organizationId)
                    ->where($alias.'.entity_type', '=', $entityType)
                    ->where($alias.'.field_key', '=', $definition->key)
                    ->where($alias.'.value_hash', '=', 'scalar');
            })
            ->orderByRaw("case when {$alias}.{$column} is null then 1 else 0 end asc")
            ->orderBy($alias.'.'.$column, $sort->normalizedDirection())
            ->orderBy($entityTable.'.id');
    }

    protected function whereProjectionExists(
        Builder $builder,
        int $organizationId,
        string $entityType,
        MetadataFieldDefinition $definition,
        ?callable $callback = null
    ): void {
        $entityTable = $builder->getModel()->getTable();

        $builder->whereExists(function ($query) use ($organizationId, $entityType, $definition, $entityTable, $callback) {
            $query->select(DB::raw(1))
                ->from('metadata_value_projections')
                ->where('metadata_value_projections.organization_id', $organizationId)
                ->where('metadata_value_projections.entity_type', $entityType)
                ->where('metadata_value_projections.field_key', $definition->key)
                ->whereColumn('metadata_value_projections.entity_id', $entityTable.'.id');

            if ($callback) {
                $callback($query);
            }
        });
    }

    protected function whereProjectionMissing(
        Builder $builder,
        int $organizationId,
        string $entityType,
        MetadataFieldDefinition $definition,
        ?callable $callback = null
    ): void {
        $entityTable = $builder->getModel()->getTable();

        $builder->whereNotExists(function ($query) use ($organizationId, $entityType, $definition, $entityTable, $callback) {
            $query->select(DB::raw(1))
                ->from('metadata_value_projections')
                ->where('metadata_value_projections.organization_id', $organizationId)
                ->where('metadata_value_projections.entity_type', $entityType)
                ->where('metadata_value_projections.field_key', $definition->key)
                ->whereColumn('metadata_value_projections.entity_id', $entityTable.'.id');

            if ($callback) {
                $callback($query);
            }
        });
    }

    protected function applyEntityTenantConstraint(Builder $builder, string $entityType, int $organizationId): void
    {
        $table = $builder->getModel()->getTable();

        if ($entityType === 'organization') {
            $builder->where($table.'.id', $organizationId);

            return;
        }

        $builder->where($table.'.organization_id', $organizationId);
    }

    protected function resolveOrganizationId(MetadataQueryRequest $request, ?int $organizationId): int
    {
        $resolved = $organizationId ?? $request->organizationId ?? $this->tenantContext->id();

        if (! $resolved) {
            throw new InvalidArgumentException('Metadata query compilation requires an organization context.');
        }

        return (int) $resolved;
    }

    protected function canonicalOperator(string $operator): string
    {
        return match ($operator) {
            'is_empty' => 'empty',
            'is_not_empty' => 'not_empty',
            'gt' => 'greater_than',
            'gte' => 'greater_than_or_equal',
            'lt' => 'less_than',
            'lte' => 'less_than_or_equal',
            'is_true' => 'true',
            'is_false' => 'false',
            default => $operator,
        };
    }

    protected function valueColumnFor(MetadataFieldDefinition $definition): string
    {
        return match ($definition->type) {
            'number', 'user', 'team' => 'value_number',
            'decimal', 'currency', 'percentage' => 'value_decimal',
            'boolean' => 'value_boolean',
            'date' => 'value_date',
            'datetime' => 'value_datetime',
            'time' => 'value_time',
            default => 'value_string',
        };
    }

    protected function normalizeValue(MetadataFieldDefinition $definition, mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->normalizeValue($definition, $item), $value);
        }

        return match ($definition->type) {
            'number', 'user', 'team' => is_numeric($value) ? (int) $value : $value,
            'decimal', 'currency', 'percentage' => is_numeric($value) ? (float) $value : $value,
            'boolean' => $this->normalizeBoolean($value),
            default => is_scalar($value) ? trim((string) $value) : $value,
        };
    }

    protected function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @return array<int, mixed>
     */
    protected function normalizeArrayValue(mixed $value): array
    {
        if (! is_array($value)) {
            return [$value];
        }

        return array_values($value);
    }

    /**
     * @return array{0: mixed, 1: mixed}
     */
    protected function normalizeBetweenValues(mixed $value): array
    {
        $values = $this->normalizeArrayValue($value);

        if (count($values) !== 2) {
            throw new InvalidArgumentException('The between metadata operator requires exactly two values.');
        }

        return [$values[0], $values[1]];
    }

    protected function escapeLike(mixed $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], (string) $value);
    }
}
