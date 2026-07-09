<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\Opportunity;
use App\Models\Organization;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class MetadataValueStorageService
{
    protected array $entityMap = [
        Lead::class => 'lead',
        Customer::class => 'customer',
        Opportunity::class => 'opportunity',
        Organization::class => 'organization',
    ];

    /**
     * @return array<string, mixed>
     */
    public function valuesFor(Model $record): array
    {
        return $record->custom_fields ?? [];
    }

    /**
     * Storage-only update operation.
     *
     * This method intentionally does not enforce dynamic validation rules,
     * render forms, update search projections, or emit timeline/automation events.
     *
     * @param  array<string, mixed>  $values
     * @return array{
     *     changed: bool,
     *     values: array<string, mixed>,
     *     changes: array<string, array{field_id: ?int, old: mixed, new: mixed}>,
     *     ignored: array<int, string>
     * }
     */
    public function mergeValues(Model $record, array $values, bool $allowUnknown = false): array
    {
        $entityType = $this->entityTypeFor($record);
        $organizationId = $this->organizationIdFor($record);
        $definitions = $this->activeDefinitions($organizationId, $entityType);
        $existing = $this->valuesFor($record);
        $next = $existing;
        $changes = [];
        $ignored = [];

        foreach ($values as $key => $value) {
            $key = (string) $key;
            $definition = $definitions->get($key);

            if (! $definition && ! $allowUnknown) {
                $ignored[] = $key;
                continue;
            }

            $old = $next[$key] ?? null;
            $normalized = $definition
                ? $this->normalizeForStorage($definition, $value)
                : $this->normalizeLegacyValue($value);

            if ($value === null) {
                unset($next[$key]);
                $normalized = null;
            } else {
                $next[$key] = $normalized;
            }

            if ($old !== $normalized) {
                $changes[$key] = [
                    'field_id' => $definition?->id,
                    'old' => $old,
                    'new' => $normalized,
                ];
            }
        }

        if ($changes !== []) {
            $record->forceFill(['custom_fields' => $next !== [] ? $next : null])->save();
        }

        return [
            'changed' => $changes !== [],
            'values' => $next,
            'changes' => $changes,
            'ignored' => $ignored,
        ];
    }

    public function entityTypeFor(Model $record): string
    {
        foreach ($this->entityMap as $class => $entityType) {
            if ($record instanceof $class) {
                return $entityType;
            }
        }

        throw new InvalidArgumentException('Model does not support metadata value storage.');
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

    protected function normalizeForStorage(MetadataFieldDefinition $definition, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($definition->type) {
            'number' => is_numeric($value) ? (int) $value : $value,
            'decimal', 'currency', 'percentage' => is_numeric($value) ? (float) $value : $value,
            'boolean' => $this->normalizeBoolean($value),
            'multi_select' => array_values(array_filter((array) $value, fn ($item) => $item !== null && $item !== '')),
            'date' => $value instanceof DateTimeInterface ? $value->format('Y-m-d') : (string) $value,
            'datetime' => $value instanceof DateTimeInterface ? $value->format(DateTimeInterface::ATOM) : (string) $value,
            'time' => $value instanceof DateTimeInterface ? $value->format('H:i:s') : (string) $value,
            'user', 'team' => is_numeric($value) ? (int) $value : $value,
            default => is_scalar($value) ? (string) $value : $value,
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

    protected function normalizeLegacyValue(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        return $value;
    }
}
