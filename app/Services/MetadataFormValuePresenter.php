<?php

namespace App\Services;

use App\Models\MetadataFieldDefinition;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MetadataFormValuePresenter
{
    public function inputName(MetadataFieldDefinition $field): string
    {
        return "custom_fields[{$field->key}]";
    }

    public function inputId(MetadataFieldDefinition $field): string
    {
        return 'custom_fields_'.preg_replace('/[^A-Za-z0-9_-]/', '_', $field->key);
    }

    public function errorKey(MetadataFieldDefinition $field): string
    {
        return "custom_fields.{$field->key}";
    }

    /**
     * @param  array<string, mixed>|null  $oldCustomFields
     */
    public function formValue(MetadataFieldDefinition $field, ?Model $record = null, ?array $oldCustomFields = null): mixed
    {
        if (is_array($oldCustomFields) && array_key_exists($field->key, $oldCustomFields)) {
            return $oldCustomFields[$field->key];
        }

        $recordValues = $record?->custom_fields ?? [];

        if (is_array($recordValues) && array_key_exists($field->key, $recordValues)) {
            return $recordValues[$field->key];
        }

        return $field->default_value;
    }

    /**
     * Keep extraction field-aware so unrendered fields are omitted and rendered
     * empty scalar values become explicit clears for the storage service.
     *
     * @param  Collection<int, array{field: MetadataFieldDefinition}>  $resolvedFields
     * @param  array<string, mixed>  $customFieldsPayload
     * @return array<string, mixed>
     */
    public function extractSubmittedValues(Collection $resolvedFields, array $customFieldsPayload): array
    {
        $values = [];

        foreach ($resolvedFields as $item) {
            $field = $item['field'];

            if (! array_key_exists($field->key, $customFieldsPayload)) {
                continue;
            }

            $values[$field->key] = $this->normalizeSubmittedValue($field, $customFieldsPayload[$field->key]);
        }

        return $values;
    }

    public function normalizeSubmittedValue(MetadataFieldDefinition $field, mixed $value): mixed
    {
        if ($field->type === 'multi_select') {
            return array_values(array_filter((array) $value, fn ($item) => $item !== null && $item !== ''));
        }

        if ($value === '') {
            return null;
        }

        return $value;
    }

    public function displayValue(MetadataFieldDefinition $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($field->type === 'boolean') {
            return $this->truthy($value) ? __('Yes') : __('No');
        }

        if ($field->type === 'multi_select') {
            $labels = collect((array) $value)
                ->map(fn ($optionValue) => $this->optionLabel($field, $optionValue))
                ->filter()
                ->values();

            return $labels->isEmpty() ? '—' : $labels->join(', ');
        }

        if (in_array($field->type, ['select', 'radio'], true)) {
            return $this->optionLabel($field, $value) ?? (string) $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format($field->type === 'date' ? 'Y-m-d' : DateTimeInterface::ATOM);
        }

        if (is_array($value)) {
            return $value === [] ? '—' : collect($value)->join(', ');
        }

        return (string) $value;
    }

    protected function optionLabel(MetadataFieldDefinition $field, mixed $value): ?string
    {
        $option = $field->options->firstWhere('value', (string) $value);

        return $option?->label;
    }

    protected function truthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
