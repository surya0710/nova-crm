<?php

namespace App\Http\Requests;

use App\Models\MetadataFieldDefinition;
use App\Models\WorkflowAction;
use App\Models\WorkflowCondition;
use App\Services\NotificationService;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use JsonException;

class StoreWorkflowRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $normalized = [];
        $jsonFields = ['trigger_config_json', 'conditions_json', 'actions_json'];
        $hasJsonPayload = $this->exists('workflow_payload_complete')
            || collect($jsonFields)->contains(fn (string $field): bool => $this->exists($field));

        if ($hasJsonPayload) {
            if ($this->input('workflow_payload_complete') !== 'workflow-builder-v1'
                || collect($jsonFields)->contains(fn (string $field): bool => ! $this->exists($field))) {
                throw ValidationException::withMessages([
                    'workflow_payload_complete' => 'The workflow builder payload is incomplete or truncated. Reload the form and try again.',
                ]);
            }

            $normalized['trigger_config'] = $this->decodeBuilderJson('trigger_config_json', 4096);
            $normalized['conditions'] = $this->decodeBuilderJson('conditions_json', 262144);
            $normalized['actions'] = $this->decodeBuilderJson('actions_json', 262144);

            if (! is_array($normalized['trigger_config'])
                || ! is_array($normalized['conditions']) || ! array_is_list($normalized['conditions'])
                || ! is_array($normalized['actions']) || ! array_is_list($normalized['actions'])) {
                throw ValidationException::withMessages([
                    'workflow_payload_complete' => 'The workflow builder payload has an invalid shape.',
                ]);
            }
        } elseif ($this->exists('trigger_config') || $this->isMethod('post')) {
            $triggerConfig = (array) $this->input('trigger_config', []);
            unset($triggerConfig['_present']);
            $normalized['trigger_config'] = $triggerConfig;
        }

        if (! $hasJsonPayload && ($this->exists('conditions') || $this->isMethod('post'))) {
            $normalized['conditions'] = $this->normalizeConditions((array) $this->input('conditions', []));
        }

        if (! $hasJsonPayload && ($this->exists('actions') || $this->isMethod('post'))) {
            $normalized['actions'] = array_map(function (mixed $action): mixed {
                if (! is_array($action)) {
                    return $action;
                }

                $configuration = (array) ($action['configuration'] ?? []);
                unset($configuration['_present']);
                $action['configuration'] = $configuration;

                return $action;
            }, (array) $this->input('actions', []));
        }

        if (isset($normalized['conditions'])) {
            $normalized['conditions'] = $this->normalizeConditions($normalized['conditions']);
        }

        $this->merge($normalized);
    }

    public function authorize(): bool
    {
        $organization = app(TenantContext::class)->get();

        return $organization && ($this->user()?->hasPermission('workflows.create', $organization) ?? false);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'trigger_type' => ['required', 'string', Rule::in(array_keys(config('workflows.triggers', [])))],
            'trigger_config' => ['present', 'array', 'size:0'],
            'status' => ['prohibited'],
            'concurrency_limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'execution_timeout_seconds' => ['sometimes', 'integer', 'min:1', 'max:300'],
            'conditions' => ['sometimes', 'array', 'max:100'],
            'conditions.*' => ['array'],
            'actions' => ['required', 'array', 'min:1', 'max:100'],
            'actions.*' => ['required', 'array'],
            'actions.*.type' => ['required', 'string', Rule::in(array_keys(config('workflows.actions', [])))],
            'actions.*.name' => ['nullable', 'string', 'max:255'],
            'actions.*.configuration' => ['required', 'array'],
            'actions.*.status' => ['sometimes', Rule::in(WorkflowAction::STATUSES)],
            'actions.*.position' => ['sometimes', 'integer', 'min:0', 'distinct'],
            'actions.*.delay_seconds' => ['prohibited'],
            'actions.*.max_attempts' => ['prohibited'],
            'actions.*.retry_delay_seconds' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $count = 0;

            foreach ($this->input('conditions', []) as $index => $condition) {
                $this->validateCondition($validator, $condition, "conditions.{$index}", 1, $count);
            }

            foreach ($this->input('actions', []) as $index => $action) {
                $definition = config('workflows.actions.'.($action['type'] ?? ''));
                if (! is_array($definition)) {
                    continue;
                }
                $entity = config('workflows.triggers', [])[$this->input('trigger_type') ?? '']['entity'] ?? null;
                if (is_string($entity) && ! in_array($entity, $definition['entities'] ?? [], true)) {
                    $validator->errors()->add("actions.{$index}.type", "This action does not support {$entity} triggers.");
                }
                foreach ($definition['fields'] as $field) {
                    if (! array_key_exists($field, $action['configuration'] ?? [])) {
                        $validator->errors()->add("actions.{$index}.configuration.{$field}", "The {$field} field is required.");
                    }
                }

                foreach ($definition['form_fields'] ?? [] as $field => $metadata) {
                    $value = $action['configuration'][$field] ?? null;
                    if (($metadata['required'] ?? false) && ($value === null || $value === '' || $value === [])) {
                        $validator->errors()->add("actions.{$index}.configuration.{$field}", "The {$metadata['label']} field is required.");
                    }

                    if (($metadata['type'] ?? null) === 'user' && $value !== null && $value !== '') {
                        $organization = app(TenantContext::class)->get();
                        if (! $organization?->users()->whereKey((int) $value)->exists()) {
                            $validator->errors()->add("actions.{$index}.configuration.{$field}", 'The selected user is not an organization member.');
                        }
                    }

                    $type = $metadata['type'] ?? null;
                    if (in_array($type, ['text', 'textarea', 'url', 'datetime-local'], true)
                        && $value !== null && ! is_string($value)) {
                        $validator->errors()->add("actions.{$index}.configuration.{$field}", 'This value must be a string.');
                    }
                    if (is_string($value)) {
                        $max = $type === 'textarea' ? 5000 : ($type === 'url' ? 2048 : 255);
                        if (mb_strlen($value) > $max) {
                            $validator->errors()->add("actions.{$index}.configuration.{$field}", "This value may not exceed {$max} characters.");
                        }
                    }
                    if ($type === 'url' && is_string($value) && $value !== '') {
                        try {
                            NotificationService::validateActionUrl($value, "actions.{$index}.configuration.{$field}");
                        } catch (ValidationException $exception) {
                            foreach ($exception->errors() as $path => $messages) {
                                foreach ($messages as $message) {
                                    $validator->errors()->add($path, $message);
                                }
                            }
                        }
                    }
                    if ($type === 'datetime-local' && is_string($value) && $value !== ''
                        && \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value) === false) {
                        $validator->errors()->add("actions.{$index}.configuration.{$field}", 'This value must be a valid local date and time.');
                    }
                    if ($type === 'select' && $value !== null && $value !== ''
                        && ! array_key_exists((string) $value, $metadata['options'] ?? [])) {
                        $validator->errors()->add("actions.{$index}.configuration.{$field}", 'The selected value is invalid.');
                    }
                    if ($type === 'key_value' && $value !== null && ! is_array($value)) {
                        $validator->errors()->add("actions.{$index}.configuration.{$field}", 'This value must be an object.');
                    } elseif ($type === 'key_value' && is_array($value)) {
                        if (count($value) > 100) {
                            $validator->errors()->add("actions.{$index}.configuration.{$field}", 'This object may contain at most 100 keys.');
                        }
                        foreach (array_keys($value) as $key) {
                            if (! $this->validMapKey($key)) {
                                $validator->errors()->add("actions.{$index}.configuration.{$field}", 'Configuration keys must be unique safe identifiers without brackets or reserved names.');
                                break;
                            }
                        }
                    }
                }

                $allowedFields = array_keys($definition['form_fields'] ?? []);
                foreach (array_keys($action['configuration'] ?? []) as $field) {
                    if (! in_array($field, $allowedFields, true)) {
                        $validator->errors()->add("actions.{$index}.configuration.{$field}", 'This configuration field is not supported.');
                    }
                }

                if (($action['type'] ?? null) === 'update_metadata' && is_array($action['configuration']['values'] ?? null)) {
                    $organization = app(TenantContext::class)->get();
                    $validKeys = MetadataFieldDefinition::withoutGlobalScopes()
                        ->where('organization_id', $organization?->id)
                        ->where('entity_type', $entity)
                        ->where('status', 'active')
                        ->whereIn('key', array_keys($action['configuration']['values']))
                        ->pluck('key')
                        ->all();
                    foreach (array_diff(array_keys($action['configuration']['values']), $validKeys) as $key) {
                        $validator->errors()->add(
                            "actions.{$index}.configuration.values.{$key}",
                            'The metadata field is unknown or inactive.',
                        );
                    }
                }

                if (strlen((string) json_encode($action['configuration'] ?? [])) > 65536) {
                    $validator->errors()->add("actions.{$index}.configuration", 'An action configuration may not exceed 64 KB.');
                }
            }

            if (strlen((string) json_encode($this->input('conditions', []))) > 262144
                || strlen((string) json_encode($this->input('actions', []))) > 262144) {
                $validator->errors()->add('workflow_payload_complete', 'The workflow definition is too large.');
            }
        });
    }

    protected function validateCondition(
        Validator $validator,
        mixed $condition,
        string $path,
        int $depth,
        int &$count,
    ): void {
        if (! is_array($condition)) {
            $validator->errors()->add($path, 'Each condition must be an object.');

            return;
        }

        $count++;

        if ($count > 500) {
            $validator->errors()->add('conditions', 'A workflow may contain at most 500 nested conditions.');

            return;
        }

        $maxDepth = (int) config('workflows.max_depth', 10);
        if ($depth > $maxDepth) {
            $validator->errors()->add($path, "Condition groups may be nested at most {$maxDepth} levels.");

            return;
        }

        $type = $condition['type'] ?? null;

        if (! in_array($type, WorkflowCondition::TYPES, true)) {
            $validator->errors()->add("{$path}.type", 'The condition type must be group or condition.');

            return;
        }

        if (isset($condition['negated']) && ! is_bool($condition['negated'])) {
            $validator->errors()->add("{$path}.negated", 'The negated field must be true or false.');
        }

        if ($type === WorkflowCondition::TYPE_GROUP) {
            if (! in_array($condition['boolean_operator'] ?? null, WorkflowCondition::BOOLEAN_OPERATORS, true)) {
                $validator->errors()->add("{$path}.boolean_operator", 'A group must use the all or any boolean operator.');
            }

            $children = $condition['conditions'] ?? null;

            if (! is_array($children) || $children === []) {
                $validator->errors()->add("{$path}.conditions", 'A condition group must contain at least one condition.');

                return;
            }

            if (count($children) > 100) {
                $validator->errors()->add("{$path}.conditions", 'A condition group may contain at most 100 direct children.');
            }

            foreach ($children as $index => $child) {
                $this->validateCondition($validator, $child, "{$path}.conditions.{$index}", $depth + 1, $count);
            }

            return;
        }

        foreach (['field', 'operator'] as $key) {
            $value = $condition[$key] ?? null;

            if (! is_string($value) || $value === '' || mb_strlen($value) > 255) {
                $validator->errors()->add("{$path}.{$key}", "A condition must have a valid {$key}.");
            }
        }

        if (is_string($condition['operator'] ?? null) && ! in_array($condition['operator'], config('workflows.operators', []), true)) {
            $validator->errors()->add("{$path}.operator", 'The selected condition operator is invalid.');
        }

        if (! array_key_exists('value', $condition)) {
            $validator->errors()->add("{$path}.value", 'A condition value is required (null is allowed).');
        }

        $operator = $condition['operator'] ?? null;
        $value = $condition['value'] ?? null;
        if ($operator === 'between' && (! is_array($value) || count($value) !== 2)) {
            $validator->errors()->add("{$path}.value", 'The between operator requires exactly two values.');
        }
        if (in_array($operator, ['in_list', 'not_in_list'], true) && (! is_array($value) || $value === [])) {
            $validator->errors()->add("{$path}.value", 'List operators require at least one value.');
        }

        if (isset($condition['conditions'])) {
            $validator->errors()->add("{$path}.conditions", 'Only condition groups may have nested conditions.');
        }
    }

    private function normalizeConditions(array $conditions): array
    {
        return array_map(function (mixed $condition): mixed {
            if (! is_array($condition)) {
                return $condition;
            }

            if (array_key_exists('negated', $condition)) {
                $condition['negated'] = filter_var($condition['negated'], FILTER_VALIDATE_BOOL);
            }

            if (($condition['type'] ?? null) === WorkflowCondition::TYPE_GROUP) {
                $condition['conditions'] = $this->normalizeConditions((array) ($condition['conditions'] ?? []));
            } elseif (in_array($condition['operator'] ?? null, ['empty', 'not_empty'], true)) {
                $condition['value'] = null;
            }

            return $condition;
        }, $conditions);
    }

    private function decodeBuilderJson(string $field, int $maxBytes): mixed
    {
        $value = $this->input($field);
        if (! is_string($value) || strlen($value) > $maxBytes) {
            throw ValidationException::withMessages([$field => 'The workflow builder payload is missing or too large.']);
        }

        try {
            return json_decode($value, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([$field => 'The workflow builder payload contains malformed JSON.']);
        }
    }

    private function validMapKey(mixed $key): bool
    {
        return is_string($key)
            && preg_match('/^[A-Za-z][A-Za-z0-9_.-]{0,127}$/', $key) === 1
            && ! in_array($key, ['__proto__', 'prototype', 'constructor'], true);
    }
}
