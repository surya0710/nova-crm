<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\ProgressUpdate;
use App\Models\Project;
use App\Models\ResourceAllocation;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MetadataValidationService
{
    protected array $modelMap = [
        'lead' => Lead::class,
        'customer' => Customer::class,
        'opportunity' => Opportunity::class,
        'organization' => Organization::class,
        'project' => Project::class,
        'task' => Task::class,
        'resource_allocation' => ResourceAllocation::class,
        'project_progress_update' => ProgressUpdate::class,
    ];

    protected array $supportedRuleKeys = [
        'min',
        'max',
        'regex',
        'before',
        'after',
        'decimal_places',
        'allowed_values',
    ];

    /**
     * Authoritative metadata validation entry point.
     *
     * @param  Collection<int, array{field: MetadataFieldDefinition}>  $resolvedFields
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(
        ?Model $record,
        Organization $organization,
        string $entityType,
        Collection $resolvedFields,
        array $payload,
        bool $allowUnknown = false,
        bool $enforceRequired = true
    ): array {
        $errors = [];
        $validated = [];
        $fieldsByKey = $resolvedFields->mapWithKeys(fn (array $item) => [$item['field']->key => $item['field']]);

        foreach ($fieldsByKey as $key => $field) {
            $submitted = array_key_exists($key, $payload);
            $value = $submitted ? $payload[$key] : null;
            $hasExisting = $this->recordHasNonEmptyValue($record, $field);
            $errorKey = "custom_fields.{$key}";

            if ($enforceRequired && $field->is_required && (! $submitted && ! $hasExisting)) {
                $errors[$errorKey][] = __('The :attribute field is required.', ['attribute' => $field->label]);

                continue;
            }

            if (! $submitted) {
                continue;
            }

            if ($enforceRequired && $field->is_required && $this->isEmptyValue($field, $value)) {
                $errors[$errorKey][] = __('The :attribute field is required.', ['attribute' => $field->label]);

                continue;
            }

            if (! $field->is_required && $this->isEmptyValue($field, $value)) {
                $validated[$key] = $this->clearValueFor($field, $value);

                continue;
            }

            $rules = $this->rulesFor($field, $organization, $entityType, $record);
            $validator = Validator::make(
                ['custom_fields' => [$key => $value]],
                [$errorKey => $rules],
                [],
                [$errorKey => $field->label],
            );

            if ($validator->fails()) {
                $errors[$errorKey] = array_values($validator->errors()->get($errorKey));

                continue;
            }

            $validated[$key] = $value;
        }

        if (! $allowUnknown) {
            foreach (array_keys($payload) as $key) {
                if (! $fieldsByKey->has((string) $key)) {
                    continue;
                }
            }
        } else {
            foreach ($payload as $key => $value) {
                if (! $fieldsByKey->has((string) $key)) {
                    $validated[(string) $key] = $value;
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $validated;
    }

    /**
     * Validate metadata definition validation_rules shape.
     *
     * @return array<string, list<string>>
     */
    public function validationRuleSchemaErrors(array $rules, ?string $fieldType = null): array
    {
        $errors = [];

        foreach ($rules as $key => $value) {
            if (! in_array($key, $this->supportedRuleKeys, true)) {
                $errors["validation_rules.{$key}"][] = __('Unsupported metadata validation rule: :rule', ['rule' => $key]);

                continue;
            }

            if (in_array($key, ['min', 'max', 'decimal_places'], true) && ! is_numeric($value)) {
                $errors["validation_rules.{$key}"][] = __('The :rule validation rule must be numeric.', ['rule' => $key]);
            }

            if (in_array($key, ['regex', 'before', 'after'], true) && ! is_string($value)) {
                $errors["validation_rules.{$key}"][] = __('The :rule validation rule must be a string.', ['rule' => $key]);
            }

            if ($key === 'allowed_values' && ! is_array($value)) {
                $errors["validation_rules.{$key}"][] = __('The allowed_values validation rule must be an array.');
            }
        }

        return $errors;
    }

    protected function rulesFor(MetadataFieldDefinition $field, Organization $organization, string $entityType, ?Model $record): array
    {
        $rules = ['nullable'];

        $rules = array_merge($rules, match ($field->type) {
            'number', 'user', 'team' => ['integer'],
            'decimal', 'currency', 'percentage' => ['numeric'],
            'boolean' => ['boolean'],
            'email' => ['string', 'email'],
            'url' => ['string', 'url'],
            'date' => ['date'],
            'datetime' => ['date'],
            'time' => [],
            'select', 'radio' => [Rule::in($this->optionValues($field))],
            'multi_select' => ['array'],
            default => ['string'],
        });

        if ($field->type === 'time') {
            $rules[] = function (string $attribute, mixed $value, \Closure $fail) {
                if (! is_string($value) || (! preg_match('/^\d{2}:\d{2}$/', $value) && ! preg_match('/^\d{2}:\d{2}:\d{2}$/', $value))) {
                    $fail(__('The :attribute must be a valid time.'));
                }
            };
        }

        if ($field->type === 'multi_select') {
            $rules[] = function (string $attribute, mixed $value, \Closure $fail) use ($field) {
                $allowed = $this->optionValues($field);

                foreach ((array) $value as $item) {
                    if (! in_array((string) $item, $allowed, true)) {
                        $fail(__('The selected :attribute is invalid.'));

                        return;
                    }
                }
            };
        }

        foreach ($this->compiledJsonRules($field) as $rule) {
            $rules[] = $rule;
        }

        if ($field->is_unique) {
            $rules[] = function (string $attribute, mixed $value, \Closure $fail) use ($field, $organization, $entityType, $record) {
                if ($this->isEmptyValue($field, $value) || is_array($value)) {
                    return;
                }

                $modelClass = $this->modelMap[$entityType] ?? null;

                if (! $modelClass) {
                    return;
                }

                $query = $modelClass::withoutGlobalScopes()
                    ->where($record instanceof Organization ? 'id' : 'organization_id', $organization->id)
                    ->where("custom_fields->{$field->key}", $value);

                if ($record?->exists) {
                    $query->whereKeyNot($record->getKey());
                }

                if ($query->exists()) {
                    $fail(__('The :attribute has already been taken.'));
                }
            };
        }

        return $rules;
    }

    protected function compiledJsonRules(MetadataFieldDefinition $field): array
    {
        $compiled = [];
        $rules = $field->validation_rules ?? [];

        if (isset($rules['min'])) {
            $compiled[] = 'min:'.$rules['min'];
        }

        if (isset($rules['max'])) {
            $compiled[] = 'max:'.$rules['max'];
        }

        if (isset($rules['regex'])) {
            $compiled[] = 'regex:'.$rules['regex'];
        }

        if (isset($rules['before'])) {
            $compiled[] = 'before:'.$rules['before'];
        }

        if (isset($rules['after'])) {
            $compiled[] = 'after:'.$rules['after'];
        }

        if (isset($rules['allowed_values']) && is_array($rules['allowed_values'])) {
            $compiled[] = Rule::in(array_map('strval', $rules['allowed_values']));
        }

        if (isset($rules['decimal_places'])) {
            $places = (int) $rules['decimal_places'];
            $compiled[] = "decimal:0,{$places}";
        }

        return $compiled;
    }

    protected function optionValues(MetadataFieldDefinition $field): array
    {
        return $field->options
            ->pluck('value')
            ->map(fn ($value) => (string) $value)
            ->all();
    }

    protected function recordHasNonEmptyValue(?Model $record, MetadataFieldDefinition $field): bool
    {
        $values = $record?->custom_fields ?? [];

        return is_array($values)
            && array_key_exists($field->key, $values)
            && ! $this->isEmptyValue($field, $values[$field->key]);
    }

    protected function isEmptyValue(MetadataFieldDefinition $field, mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return $field->type === 'multi_select'
            && array_values(array_filter((array) $value, fn ($item) => $item !== null && $item !== '')) === [];
    }

    protected function clearValueFor(MetadataFieldDefinition $field, mixed $value): mixed
    {
        return $field->type === 'multi_select' && is_array($value) ? [] : null;
    }
}
