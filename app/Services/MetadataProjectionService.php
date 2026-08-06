<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\MetadataValueProjection;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\ProgressUpdate;
use App\Models\Project;
use App\Models\ResourceAllocation;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class MetadataProjectionService
{
    protected array $entityMap = [
        'lead' => Lead::class,
        'customer' => Customer::class,
        'opportunity' => Opportunity::class,
        'organization' => Organization::class,
        'project' => Project::class,
        'task' => Task::class,
        'resource_allocation' => ResourceAllocation::class,
        'project_progress_update' => ProgressUpdate::class,
    ];

    /**
     * Synchronize projection rows for one metadata-enabled entity.
     *
     * Projection rows are derived indexes only; this method always reads the
     * persisted entity JSON instead of trusting request input.
     *
     * @return array{deleted: int, projected: int}
     */
    public function sync(Model $record): array
    {
        $entityType = $this->entityTypeFor($record);
        $organizationId = $this->organizationIdFor($record);
        $rows = $this->projectionRowsFor($record);

        return DB::transaction(function () use ($entityType, $organizationId, $record, $rows) {
            $deleted = MetadataValueProjection::withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->where('entity_type', $entityType)
                ->where('entity_id', $record->getKey())
                ->delete();

            if ($rows !== []) {
                MetadataValueProjection::query()->insert($rows);
            }

            return [
                'deleted' => $deleted,
                'projected' => count($rows),
            ];
        });
    }

    /**
     * @return array{entities: int, projected: int}
     */
    public function rebuildForOrganizationEntity(int $organizationId, string $entityType, int $chunkSize = 500): array
    {
        $this->assertSupportedEntityType($entityType);

        MetadataValueProjection::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('entity_type', $entityType)
            ->delete();

        $summary = ['entities' => 0, 'projected' => 0];

        $this->entityQuery($organizationId, $entityType)
            ->chunkById($chunkSize, function ($records) use (&$summary) {
                foreach ($records as $record) {
                    $result = $this->sync($record);
                    $summary['entities']++;
                    $summary['projected'] += $result['projected'];
                }
            });

        return $summary;
    }

    /**
     * @return array{entities: int, projected: int}
     */
    public function rebuildForField(MetadataFieldDefinition $definition, int $chunkSize = 500): array
    {
        $organizationId = (int) $definition->organization_id;
        $entityType = (string) $definition->entity_type;

        $this->assertSupportedEntityType($entityType);

        MetadataValueProjection::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('entity_type', $entityType)
            ->where('field_key', $definition->key)
            ->delete();

        if ($definition->status !== 'active') {
            return ['entities' => 0, 'projected' => 0];
        }

        $summary = ['entities' => 0, 'projected' => 0];

        $this->entityQuery($organizationId, $entityType)
            ->chunkById($chunkSize, function ($records) use (&$summary, $definition) {
                foreach ($records as $record) {
                    $rows = $this->projectionRowsForDefinition($record, $definition);

                    if ($rows !== []) {
                        MetadataValueProjection::query()->insert($rows);
                    }

                    $summary['entities']++;
                    $summary['projected'] += count($rows);
                }
            });

        return $summary;
    }

    /**
     * @return array{
     *     drifted: bool,
     *     missing: array<int, string>,
     *     stale: array<int, string>,
     *     extra: array<int, string>,
     *     expected: int,
     *     actual: int
     * }
     */
    public function detectDrift(Model $record): array
    {
        $entityType = $this->entityTypeFor($record);
        $organizationId = $this->organizationIdFor($record);

        $expected = collect($this->projectionRowsFor($record))
            ->mapWithKeys(fn (array $row) => [$this->rowKey($row) => $this->comparableRow($row)])
            ->all();

        $actual = MetadataValueProjection::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $record->getKey())
            ->get()
            ->mapWithKeys(fn (MetadataValueProjection $projection) => [
                $this->rowKey($projection->getAttributes()) => $this->comparableRow($projection->toArray()),
            ])
            ->all();

        $missing = array_values(array_diff(array_keys($expected), array_keys($actual)));
        $extra = array_values(array_diff(array_keys($actual), array_keys($expected)));
        $stale = [];

        foreach (array_intersect(array_keys($expected), array_keys($actual)) as $key) {
            if ($expected[$key] !== $actual[$key]) {
                $stale[] = $key;
            }
        }

        return [
            'drifted' => $missing !== [] || $stale !== [] || $extra !== [],
            'missing' => $missing,
            'stale' => $stale,
            'extra' => $extra,
            'expected' => count($expected),
            'actual' => count($actual),
        ];
    }

    /**
     * @return array{deleted: int, projected: int}
     */
    public function repairDrift(Model $record): array
    {
        return $this->sync($record);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function projectionRowsFor(Model $record): array
    {
        $entityType = $this->entityTypeFor($record);
        $organizationId = $this->organizationIdFor($record);
        $definitions = $this->activeDefinitions($organizationId, $entityType);
        $values = $record->custom_fields ?? [];

        if (! is_array($values) || $values === []) {
            return [];
        }

        $rows = [];

        foreach ($values as $key => $value) {
            $definition = $definitions->get((string) $key);

            if (! $definition) {
                continue;
            }

            array_push($rows, ...$this->rowsForValue($record, $definition, $value));
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function projectionRowsForDefinition(Model $record, MetadataFieldDefinition $definition): array
    {
        $values = $record->custom_fields ?? [];

        if (! is_array($values) || ! array_key_exists($definition->key, $values)) {
            return [];
        }

        return $this->rowsForValue($record, $definition, $values[$definition->key]);
    }

    protected function entityTypeFor(Model $record): string
    {
        foreach ($this->entityMap as $entityType => $class) {
            if ($record instanceof $class) {
                return $entityType;
            }
        }

        throw new InvalidArgumentException('Model does not support metadata projection.');
    }

    protected function organizationIdFor(Model $record): int
    {
        if ($record instanceof Organization) {
            return (int) $record->id;
        }

        return (int) $record->organization_id;
    }

    protected function activeDefinitions(int $organizationId, string $entityType)
    {
        return MetadataFieldDefinition::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('entity_type', $entityType)
            ->where('status', 'active')
            ->get()
            ->keyBy('key');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function rowsForValue(Model $record, MetadataFieldDefinition $definition, mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if ($definition->type === 'multi_select') {
            $values = array_values(array_filter((array) $value, fn ($item) => $item !== null && $item !== ''));

            return array_map(
                fn ($item) => $this->buildRow($record, $definition, $item, $this->valueHash($item)),
                $values
            );
        }

        return [$this->buildRow($record, $definition, $value, 'scalar')];
    }

    protected function buildRow(Model $record, MetadataFieldDefinition $definition, mixed $value, string $valueHash): array
    {
        $now = now();
        $fieldType = (string) $definition->type;
        $valueString = $this->stringValue($value);

        $row = [
            'organization_id' => $this->organizationIdFor($record),
            'metadata_field_definition_id' => $definition->id,
            'entity_type' => $this->entityTypeFor($record),
            'entity_id' => $record->getKey(),
            'field_key' => $definition->key,
            'field_type' => $fieldType,
            'value_hash' => $valueHash,
            'value_string' => $valueString,
            'value_text' => is_scalar($value) ? (string) $value : null,
            'value_number' => null,
            'value_decimal' => null,
            'value_boolean' => null,
            'value_date' => null,
            'value_datetime' => null,
            'value_time' => null,
            'value_json' => $this->jsonValue($value),
            'normalized_search_text' => $valueString !== null ? strtolower($valueString) : null,
            'is_sensitive' => (bool) $definition->is_sensitive,
            'definition_status' => $definition->status,
            'source_updated_at' => $record->updated_at?->toDateTimeString(),
            'projected_at' => $now->toDateTimeString(),
            'created_at' => $now->toDateTimeString(),
            'updated_at' => $now->toDateTimeString(),
        ];

        match ($fieldType) {
            'number', 'user', 'team' => $row['value_number'] = is_numeric($value) ? (int) $value : null,
            'decimal', 'currency', 'percentage' => $row['value_decimal'] = is_numeric($value) ? number_format((float) $value, 6, '.', '') : null,
            'boolean' => $row['value_boolean'] = (bool) $value,
            'date' => $row['value_date'] = $this->dateValue($value),
            'datetime' => $row['value_datetime'] = $this->dateTimeValue($value),
            'time' => $row['value_time'] = $this->timeValue($value),
            default => null,
        };

        return $row;
    }

    protected function entityQuery(int $organizationId, string $entityType): Builder
    {
        $this->assertSupportedEntityType($entityType);

        /** @var class-string<Model> $class */
        $class = $this->entityMap[$entityType];
        $query = $class::withoutGlobalScopes();

        if ($entityType === 'organization') {
            return $query->whereKey($organizationId);
        }

        return $query->where('organization_id', $organizationId);
    }

    protected function assertSupportedEntityType(string $entityType): void
    {
        if (! array_key_exists($entityType, $this->entityMap)) {
            throw new InvalidArgumentException("Unsupported metadata projection entity type [{$entityType}].");
        }
    }

    protected function stringValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        return substr((string) $value, 0, 255);
    }

    protected function jsonValue(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    protected function valueHash(mixed $value): string
    {
        return hash('sha256', $this->jsonValue($value));
    }

    protected function dateValue(mixed $value): ?string
    {
        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    protected function dateTimeValue(mixed $value): ?string
    {
        try {
            return Carbon::parse((string) $value)->toDateTimeString();
        } catch (Throwable) {
            return null;
        }
    }

    protected function timeValue(mixed $value): ?string
    {
        try {
            return Carbon::parse((string) $value)->format('H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    protected function rowKey(array $row): string
    {
        return implode('|', [
            $row['organization_id'],
            $row['entity_type'],
            $row['entity_id'],
            $row['field_key'],
            $row['value_hash'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function comparableRow(array $row): array
    {
        return [
            'metadata_field_definition_id' => (int) $row['metadata_field_definition_id'],
            'field_type' => $row['field_type'],
            'value_string' => $row['value_string'] ?? null,
            'value_text' => $row['value_text'] ?? null,
            'value_number' => isset($row['value_number']) ? (int) $row['value_number'] : null,
            'value_decimal' => isset($row['value_decimal']) ? number_format((float) $row['value_decimal'], 6, '.', '') : null,
            'value_boolean' => isset($row['value_boolean']) ? (bool) $row['value_boolean'] : null,
            'value_date' => isset($row['value_date']) ? substr((string) $row['value_date'], 0, 10) : null,
            'value_datetime' => isset($row['value_datetime']) ? Carbon::parse($row['value_datetime'])->toDateTimeString() : null,
            'value_time' => isset($row['value_time']) ? (string) $row['value_time'] : null,
            'value_json' => $this->jsonComparable($row['value_json'] ?? null),
            'normalized_search_text' => $row['normalized_search_text'] ?? null,
            'is_sensitive' => (bool) ($row['is_sensitive'] ?? false),
            'definition_status' => $row['definition_status'],
        ];
    }

    protected function jsonComparable(mixed $value): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->jsonValue($decoded);
            }
        }

        return $this->jsonValue($value);
    }
}
