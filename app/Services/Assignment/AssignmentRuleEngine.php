<?php

namespace App\Services\Assignment;

use App\Models\AssignmentPool;
use App\Models\AssignmentRule;

/**
 * Pure matching + strategy execution. No history persistence.
 */
class AssignmentRuleEngine
{
    public function __construct(
        protected AssignmentStrategyRegistry $strategies,
    ) {}

    public function resolve(AssignmentContext $context): AssignmentResult
    {
        $rule = $this->findMatchingRule($context);

        if (! $rule) {
            return AssignmentResult::unassigned(message: 'No matching assignment rule.');
        }

        $pool = $rule->assignment_pool_id
            ? AssignmentPool::query()
                ->withoutGlobalScopes()
                ->where('organization_id', $context->organizationId)
                ->whereKey($rule->assignment_pool_id)
                ->first()
            : null;

        if (! $pool || ! $pool->is_active) {
            return AssignmentResult::unassigned(
                message: 'Matched rule has no active pool.',
                rule: $rule,
            );
        }

        $strategyKey = $rule->strategy ?: $pool->strategy;

        if (! $strategyKey || ! $this->strategies->has($strategyKey)) {
            return AssignmentResult::unassigned(
                message: 'Matched rule has unknown strategy.',
                rule: $rule,
                pool: $pool,
            );
        }

        $result = $this->strategies->get($strategyKey)->assign($pool, $context);

        return new AssignmentResult(
            assignee: $result->assignee,
            strategy: $result->strategy ?? $strategyKey,
            rule: $rule,
            pool: $pool,
            matched: true,
            message: $result->message,
        );
    }

    public function findMatchingRule(AssignmentContext $context): ?AssignmentRule
    {
        $rules = AssignmentRule::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $context->organizationId)
            ->where('entity_type', $context->entityType)
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        $default = null;

        foreach ($rules as $rule) {
            if ($rule->is_default) {
                $default ??= $rule;

                continue;
            }

            if ($this->matchesConditions($rule, $context)) {
                return $rule;
            }
        }

        return $default;
    }

    public function matchesConditions(AssignmentRule $rule, AssignmentContext $context): bool
    {
        $conditions = $rule->conditions ?? [];

        if ($conditions === []) {
            // Empty non-default conditions match everything (explicit catch-all).
            return true;
        }

        foreach (['source', 'status', 'country', 'lead_type', 'pipeline'] as $field) {
            if (! array_key_exists($field, $conditions) || $conditions[$field] === null || $conditions[$field] === '') {
                continue;
            }

            $expected = $this->normalizeScalar($conditions[$field]);
            $actual = $this->normalizeScalar($context->attribute($field));

            if ($actual === null || $actual !== $expected) {
                return false;
            }
        }

        if (isset($conditions['metadata']) && is_array($conditions['metadata'])) {
            $metadata = $context->attribute('metadata', []);
            if (! is_array($metadata)) {
                $metadata = [];
            }

            foreach ($conditions['metadata'] as $key => $expected) {
                if ($expected === null || $expected === '') {
                    continue;
                }

                $actual = $metadata[$key] ?? null;
                if ($this->normalizeScalar($actual) !== $this->normalizeScalar($expected)) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function normalizeScalar(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        $string = trim((string) $value);

        return $string === '' ? null : mb_strtolower($string);
    }
}
