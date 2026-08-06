<?php

namespace App\Services\Export\Adapters;

use App\Contracts\Export\ExportableEntityInterface;
use App\Models\Organization;
use App\Services\Export\Concerns\ResolvesExportSelection;
use App\Services\Export\ExportColumnDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

abstract class AbstractExportAdapter implements ExportableEntityInterface
{
    use ResolvesExportSelection;

    abstract public function entityType(): string;

    abstract public function entityLabel(): string;

    abstract public function module(): string;

    abstract public function permission(): string;

    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    abstract public function columnDefinitions(): array;

    public function defaultColumns(): array
    {
        return array_values(array_map(
            static fn (ExportColumnDefinition $col) => $col->key,
            array_filter(
                $this->columnDefinitions(),
                static fn (ExportColumnDefinition $col) => $col->default && ! $col->hidden && ! $col->sensitive
            )
        ));
    }

    public function hiddenColumns(): array
    {
        return array_values(array_map(
            static fn (ExportColumnDefinition $col) => $col->key,
            array_filter(
                $this->columnDefinitions(),
                static fn (ExportColumnDefinition $col) => $col->hidden
            )
        ));
    }

    public function resolveQuery(Organization $organization, array $selection): Builder
    {
        return $this->baseOrganizationQuery($this->modelClass(), $organization, $selection);
    }

    public function eagerLoads(array $columns): array
    {
        $loads = [];
        $byKey = $this->columnsByKey();

        foreach ($columns as $key) {
            $col = $byKey[$key] ?? null;
            if ($col) {
                foreach ($col->eager as $relation) {
                    $loads[] = $relation;
                }
            }
        }

        return array_values(array_unique($loads));
    }

    public function mapRow(Model $record, array $columns): array
    {
        $byKey = $this->columnsByKey();
        $row = [];

        foreach ($columns as $key) {
            $col = $byKey[$key] ?? null;
            if (! $col || $col->sensitive) {
                continue;
            }

            $row[$key] = $this->resolveColumnValue($record, $col);
        }

        return $row;
    }

    /**
     * @return array<string, ExportColumnDefinition>
     */
    protected function columnsByKey(): array
    {
        $map = [];
        foreach ($this->columnDefinitions() as $col) {
            $map[$col->key] = $col;
        }

        return $map;
    }

    protected function resolveColumnValue(Model $record, ExportColumnDefinition $col): mixed
    {
        if ($col->dataType === ExportColumnDefinition::TYPE_COMPUTED
            || $col->dataType === ExportColumnDefinition::TYPE_RELATIONSHIP) {
            $custom = $this->computedValue($record, $col->key);
            if ($custom !== null || array_key_exists($col->key, $this->computedOverrides($record))) {
                return $custom;
            }
        }

        $attribute = $col->attribute ?? $col->key;
        $value = data_get($record, $attribute);

        return $this->formatValue($value, $col);
    }

    /**
     * Override in adapters for computed / relationship display values.
     *
     * @return array<string, mixed>
     */
    protected function computedOverrides(Model $record): array
    {
        return [];
    }

    protected function computedValue(Model $record, string $key): mixed
    {
        $overrides = $this->computedOverrides($record);

        return array_key_exists($key, $overrides) ? $overrides[$key] : null;
    }

    protected function formatValue(mixed $value, ExportColumnDefinition $col): mixed
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof Carbon) {
            return match ($col->dataType) {
                ExportColumnDefinition::TYPE_DATE => $value->format('Y-m-d'),
                ExportColumnDefinition::TYPE_DATETIME => $value->format('Y-m-d H:i:s'),
                default => $value->toDateTimeString(),
            };
        }

        if (is_bool($value) || $col->dataType === ExportColumnDefinition::TYPE_BOOLEAN) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $value;
    }

    /**
     * Optional filter hook for filtered / complete dataset exports.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        $status = Arr::get($filters, 'status');
        if ($status && in_array('status', $query->getModel()->getFillable(), true)) {
            $query->where($query->getModel()->getTable().'.status', $status);
        }
    }
}
