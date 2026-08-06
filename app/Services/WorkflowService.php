<?php

namespace App\Services;

use App\Models\MetadataFieldDefinition;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowCondition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkflowService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function create(Organization $organization, array $data, User $actor): Workflow
    {
        $this->validateDefinition($organization, $data);

        return DB::transaction(function () use ($organization, $data, $actor): Workflow {
            $workflow = Workflow::query()->create([
                'organization_id' => $organization->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'trigger_type' => $data['trigger_type'],
                'trigger_config' => $data['trigger_config'] ?? [],
                'status' => Workflow::STATUS_DRAFT,
                'version' => 1,
                'concurrency_limit' => $data['concurrency_limit'] ?? 1,
                'execution_timeout_seconds' => $data['execution_timeout_seconds'] ?? 300,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->syncDefinition($workflow, $data);
            $this->auditLogger->log($workflow, 'workflow_created', [
                'name' => $workflow->name,
                'trigger_type' => $workflow->trigger_type,
            ], $actor);

            return $this->freshDefinition($workflow);
        });
    }

    public function update(Workflow $workflow, array $data, User $actor): Workflow
    {
        return DB::transaction(function () use ($workflow, $data, $actor): Workflow {
            $workflow = Workflow::query()->lockForUpdate()->findOrFail($workflow->id);
            $definition = $this->effectiveDefinition($workflow, $data);
            $this->validateDefinition($workflow->organization, $definition);
            $before = $workflow->only([
                'name', 'description', 'trigger_type', 'trigger_config', 'status',
                'version', 'concurrency_limit', 'execution_timeout_seconds',
            ]);

            $workflow->fill([
                'name' => $data['name'] ?? $workflow->name,
                'description' => array_key_exists('description', $data) ? $data['description'] : $workflow->description,
                'trigger_type' => $data['trigger_type'] ?? $workflow->trigger_type,
                'trigger_config' => array_key_exists('trigger_config', $data) ? $data['trigger_config'] : $workflow->trigger_config,
                'concurrency_limit' => $data['concurrency_limit'] ?? $workflow->concurrency_limit,
                'execution_timeout_seconds' => $data['execution_timeout_seconds'] ?? $workflow->execution_timeout_seconds,
                'version' => $workflow->version + 1,
                'updated_by' => $actor->id,
            ])->save();

            $this->syncDefinition($workflow, $definition);
            $this->auditLogger->log($workflow, 'workflow_updated', [
                'before' => $before,
                'after' => $workflow->only(array_keys($before)),
            ], $actor);

            return $this->freshDefinition($workflow);
        });
    }

    public function delete(Workflow $workflow, User $actor): void
    {
        DB::transaction(function () use ($workflow, $actor): void {
            $workflow = Workflow::query()->lockForUpdate()->findOrFail($workflow->id);
            $this->auditLogger->log($workflow, 'workflow_deleted', [
                'name' => $workflow->name,
                'version' => $workflow->version,
            ], $actor);
            $workflow->delete();
        });
    }

    public function enable(Workflow $workflow, User $actor): Workflow
    {
        return DB::transaction(function () use ($workflow, $actor): Workflow {
            $workflow = Workflow::query()->lockForUpdate()->findOrFail($workflow->id);

            if (! $workflow->actions()->where('status', 'active')->exists()) {
                throw ValidationException::withMessages([
                    'actions' => 'A workflow must have at least one active action before it can be enabled.',
                ]);
            }

            $before = $workflow->status;
            $workflow->update([
                'status' => Workflow::STATUS_ACTIVE,
                'enabled_at' => now(),
                'enabled_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->auditLogger->log($workflow, 'workflow_enabled', [
                'before_status' => $before,
                'version' => $workflow->version,
            ], $actor);

            return $this->freshDefinition($workflow);
        });
    }

    public function disable(Workflow $workflow, User $actor): Workflow
    {
        return DB::transaction(function () use ($workflow, $actor): Workflow {
            $workflow = Workflow::query()->lockForUpdate()->findOrFail($workflow->id);
            $before = $workflow->status;
            $workflow->update([
                'status' => Workflow::STATUS_DISABLED,
                'enabled_at' => null,
                'enabled_by' => null,
                'updated_by' => $actor->id,
            ]);

            $this->auditLogger->log($workflow, 'workflow_disabled', [
                'before_status' => $before,
                'version' => $workflow->version,
            ], $actor);

            return $this->freshDefinition($workflow);
        });
    }

    protected function syncDefinition(Workflow $workflow, array $data): void
    {
        $workflow->conditions()->delete();

        foreach ($data['conditions'] ?? [] as $position => $condition) {
            $this->createCondition($workflow, $condition, null, $position);
        }

        $workflow->actions()->delete();

        foreach ($data['actions'] ?? [] as $position => $action) {
            $workflow->actions()->create([
                'organization_id' => $workflow->organization_id,
                'workflow_version' => $workflow->version,
                'type' => $action['type'],
                'name' => $action['name'] ?? null,
                'configuration' => $action['configuration'],
                'status' => $action['status'] ?? 'active',
                'position' => $action['position'] ?? $position,
            ]);
        }
    }

    /**
     * Build the complete definition that the next immutable version will execute.
     */
    protected function effectiveDefinition(Workflow $workflow, array $data): array
    {
        $conditions = $workflow->conditions()
            ->where('workflow_version', $workflow->version)
            ->get();
        $conditionsByParent = $conditions->groupBy(
            fn (WorkflowCondition $condition): int => (int) ($condition->parent_condition_id ?? 0)
        );

        return [
            ...$data,
            'trigger_type' => $data['trigger_type'] ?? $workflow->trigger_type,
            'trigger_config' => array_key_exists('trigger_config', $data)
                ? $data['trigger_config']
                : $workflow->trigger_config,
            'execution_timeout_seconds' => $data['execution_timeout_seconds'] ?? $workflow->execution_timeout_seconds,
            'conditions' => array_key_exists('conditions', $data)
                ? $data['conditions']
                : $conditionsByParent->get(0, collect())
                    ->map(fn (WorkflowCondition $condition): array => $this->conditionDefinition($condition, $conditionsByParent))
                    ->values()
                    ->all(),
            'actions' => array_key_exists('actions', $data)
                ? $data['actions']
                : $workflow->actions()
                    ->where('workflow_version', $workflow->version)
                    ->get()
                    ->map(fn ($action): array => [
                        'type' => $action->type,
                        'name' => $action->name,
                        'configuration' => $action->configuration,
                        'status' => $action->status,
                        'position' => $action->position,
                    ])
                    ->all(),
        ];
    }

    protected function conditionDefinition(WorkflowCondition $condition, Collection $conditionsByParent): array
    {
        $definition = [
            'type' => $condition->type,
            'negated' => $condition->negated,
            'position' => $condition->position,
        ];

        if ($condition->type === WorkflowCondition::TYPE_GROUP) {
            return [
                ...$definition,
                'boolean_operator' => $condition->boolean_operator,
                'conditions' => $conditionsByParent->get($condition->id, collect())
                    ->map(fn (WorkflowCondition $child): array => $this->conditionDefinition($child, $conditionsByParent))
                    ->values()
                    ->all(),
            ];
        }

        return [
            ...$definition,
            'field' => $condition->field,
            'operator' => $condition->operator,
            'value' => $condition->value,
        ];
    }

    protected function createCondition(
        Workflow $workflow,
        array $data,
        ?WorkflowCondition $parent,
        int $position,
    ): WorkflowCondition {
        $condition = $workflow->conditions()->create([
            'organization_id' => $workflow->organization_id,
            'workflow_version' => $workflow->version,
            'parent_condition_id' => $parent?->id,
            'type' => $data['type'],
            'boolean_operator' => $data['type'] === WorkflowCondition::TYPE_GROUP
                ? $data['boolean_operator']
                : null,
            'field' => $data['type'] === WorkflowCondition::TYPE_CONDITION ? $data['field'] : null,
            'operator' => $data['type'] === WorkflowCondition::TYPE_CONDITION ? $data['operator'] : null,
            'value' => $data['type'] === WorkflowCondition::TYPE_CONDITION ? $data['value'] : null,
            'negated' => $data['negated'] ?? false,
            'position' => $data['position'] ?? $position,
        ]);

        foreach ($data['conditions'] ?? [] as $childPosition => $child) {
            $this->createCondition($workflow, $child, $condition, $childPosition);
        }

        return $condition;
    }

    protected function freshDefinition(Workflow $workflow): Workflow
    {
        return $workflow->fresh(['rootConditions.childrenRecursive', 'actions']);
    }

    protected function validateDefinition(Organization $organization, array $data): void
    {
        $trigger = $data['trigger_type'] ?? null;
        $triggerDefinition = is_string($trigger)
            ? (config('workflows.triggers', [])[$trigger] ?? null)
            : null;
        if (! is_array($triggerDefinition)) {
            throw ValidationException::withMessages(['trigger_type' => 'The selected workflow trigger is invalid.']);
        }
        if (($data['trigger_config'] ?? []) !== []) {
            throw ValidationException::withMessages([
                'trigger_config' => 'Trigger configuration is not supported in Phase 9.2. Use workflow conditions instead.',
            ]);
        }

        $timeout = (int) ($data['execution_timeout_seconds'] ?? 300);
        if ($timeout < 1 || $timeout > 300) {
            throw ValidationException::withMessages([
                'execution_timeout_seconds' => 'The execution timeout must be between 1 and 300 seconds.',
            ]);
        }

        $conditionCount = 0;
        $this->validateConditions($data['conditions'] ?? [], 1, $conditionCount);

        foreach ($data['actions'] ?? [] as $index => $action) {
            $definition = config('workflows.actions.'.($action['type'] ?? ''));
            if (! is_array($definition)) {
                throw ValidationException::withMessages(["actions.{$index}.type" => 'The selected workflow action is invalid.']);
            }
            if (! in_array($triggerDefinition['entity'], $definition['entities'] ?? [], true)) {
                throw ValidationException::withMessages(["actions.{$index}.type" => 'This action is incompatible with the workflow trigger.']);
            }

            $configuration = $action['configuration'] ?? [];
            if (! is_array($configuration) || strlen((string) json_encode($configuration)) > 65536) {
                throw ValidationException::withMessages([
                    "actions.{$index}.configuration" => 'An action configuration must be an object no larger than 64 KB.',
                ]);
            }
            foreach ($definition['fields'] ?? [] as $field) {
                if (! array_key_exists($field, $configuration)
                    || $configuration[$field] === null
                    || $configuration[$field] === ''
                    || $configuration[$field] === []) {
                    throw ValidationException::withMessages([
                        "actions.{$index}.configuration.{$field}" => "The {$field} field is required.",
                    ]);
                }
            }
            foreach (($definition['form_fields'] ?? []) as $field => $metadata) {
                $value = $configuration[$field] ?? null;
                if (($metadata['type'] ?? null) === 'user'
                    && isset($configuration[$field])
                    && ! $organization->users()->whereKey((int) $configuration[$field])->exists()) {
                    throw ValidationException::withMessages([
                        "actions.{$index}.configuration.{$field}" => 'The selected user is not an organization member.',
                    ]);
                }
                if (is_string($value)) {
                    $max = ($metadata['type'] ?? null) === 'textarea' ? 5000 : (($metadata['type'] ?? null) === 'url' ? 2048 : 255);
                    if (mb_strlen($value) > $max) {
                        throw ValidationException::withMessages([
                            "actions.{$index}.configuration.{$field}" => "This value may not exceed {$max} characters.",
                        ]);
                    }
                }
                if (($metadata['type'] ?? null) === 'url') {
                    if ($value !== null && ! is_string($value)) {
                        throw ValidationException::withMessages([
                            "actions.{$index}.configuration.{$field}" => 'This value must be a string.',
                        ]);
                    }
                    NotificationService::validateActionUrl($value, "actions.{$index}.configuration.{$field}");
                }
                if (($metadata['type'] ?? null) === 'datetime-local' && is_string($value) && $value !== ''
                    && \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value) === false) {
                    throw ValidationException::withMessages([
                        "actions.{$index}.configuration.{$field}" => 'This value must be a valid local date and time.',
                    ]);
                }
            }
            foreach (array_keys($configuration) as $field) {
                if (! array_key_exists($field, $definition['form_fields'] ?? [])) {
                    throw ValidationException::withMessages([
                        "actions.{$index}.configuration.{$field}" => 'This configuration field is not supported.',
                    ]);
                }
            }
            if (($action['type'] ?? null) === 'update_metadata' && is_array($configuration['values'] ?? null)) {
                $validKeys = MetadataFieldDefinition::withoutGlobalScopes()
                    ->where('organization_id', $organization->id)
                    ->where('entity_type', $triggerDefinition['entity'])
                    ->where('status', 'active')
                    ->whereIn('key', array_keys($configuration['values']))
                    ->pluck('key')
                    ->all();
                if ($invalid = array_diff(array_keys($configuration['values']), $validKeys)) {
                    throw ValidationException::withMessages([
                        "actions.{$index}.configuration.values" => 'Unknown or inactive metadata fields: '.implode(', ', $invalid).'.',
                    ]);
                }
            }
        }
    }

    protected function validateConditions(array $conditions, int $depth, int &$count): void
    {
        $maxDepth = (int) config('workflows.max_depth', 10);
        if ($depth > $maxDepth) {
            throw ValidationException::withMessages(['conditions' => "Condition groups may be nested at most {$maxDepth} levels."]);
        }

        foreach ($conditions as $condition) {
            if (! is_array($condition) || ++$count > 500) {
                throw ValidationException::withMessages(['conditions' => 'The workflow condition tree is invalid or too large.']);
            }
            if (($condition['type'] ?? null) === WorkflowCondition::TYPE_GROUP) {
                if (! in_array($condition['boolean_operator'] ?? null, WorkflowCondition::BOOLEAN_OPERATORS, true)
                    || empty($condition['conditions'])
                    || ! is_array($condition['conditions'])) {
                    throw ValidationException::withMessages(['conditions' => 'Each condition group requires an operator and children.']);
                }
                $this->validateConditions($condition['conditions'], $depth + 1, $count);
            } elseif (($condition['type'] ?? null) !== WorkflowCondition::TYPE_CONDITION
                || ! in_array($condition['operator'] ?? null, config('workflows.operators', []), true)
                || ! is_string($condition['field'] ?? null)
                || $condition['field'] === ''
                || ! array_key_exists('value', $condition)) {
                throw ValidationException::withMessages(['conditions' => 'Each condition requires a valid field, operator, and value.']);
            }
        }
    }
}
