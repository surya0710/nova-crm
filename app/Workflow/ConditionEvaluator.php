<?php

namespace App\Workflow;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Arr;
use Stringable;
use Throwable;

class ConditionEvaluator
{
    public const OPERATORS = [
        'equals', 'not_equals', 'contains', 'does_not_contain', 'starts_with',
        'ends_with', 'greater_than', 'greater_than_equal', 'less_than',
        'less_than_equal', 'between', 'in_list', 'not_in_list', 'empty', 'not_empty',
    ];

    /** @param array<int, mixed> $nodes */
    public function evaluate(array $nodes, array $snapshot, ?callable $log = null, string $boolean = 'all'): bool
    {
        if ($nodes === []) {
            return true;
        }

        $results = array_map(fn ($node) => $this->evaluateNode($node, $snapshot, $log), $nodes);

        return $boolean === 'any'
            ? in_array(true, $results, true)
            : ! in_array(false, $results, true);
    }

    public function compare(mixed $actual, string $operator, mixed $expected = null): bool
    {
        if (! in_array($operator, self::OPERATORS, true)) {
            throw new \InvalidArgumentException("Unsupported workflow operator [{$operator}].");
        }

        return match ($operator) {
            'empty' => $this->isEmpty($actual),
            'not_empty' => ! $this->isEmpty($actual),
            'equals' => $this->equal($actual, $expected),
            'not_equals' => ! $this->equal($actual, $expected),
            'contains' => $this->contains($actual, $expected),
            'does_not_contain' => ! $this->contains($actual, $expected),
            'starts_with' => $actual !== null && $expected !== null
                && str_starts_with($this->string($actual), $this->string($expected)),
            'ends_with' => $actual !== null && $expected !== null
                && str_ends_with($this->string($actual), $this->string($expected)),
            'greater_than' => $actual !== null && $expected !== null && $this->order($actual, $expected) > 0,
            'greater_than_equal' => $actual !== null && $expected !== null && $this->order($actual, $expected) >= 0,
            'less_than' => $actual !== null && $expected !== null && $this->order($actual, $expected) < 0,
            'less_than_equal' => $actual !== null && $expected !== null && $this->order($actual, $expected) <= 0,
            'between' => is_array($expected) && count($expected) === 2
                && $actual !== null && $expected[0] !== null && $expected[1] !== null
                && $this->order($expected[0], $expected[1]) <= 0
                && $this->order($actual, $expected[0]) >= 0
                && $this->order($actual, $expected[1]) <= 0,
            'in_list' => $this->inList($actual, $expected),
            'not_in_list' => ! $this->inList($actual, $expected),
        };
    }

    protected function evaluateNode(mixed $node, array $snapshot, ?callable $log): bool
    {
        $data = is_object($node) ? [
            'id' => $node->id ?? null,
            'type' => $node->type,
            'boolean_operator' => $node->boolean_operator,
            'field' => $node->field,
            'operator' => $node->operator,
            'value' => $node->value,
            'negated' => $node->negated,
            'conditions' => method_exists($node, 'relationLoaded') && $node->relationLoaded('childrenRecursive')
                ? $node->childrenRecursive->all()
                : (isset($node->children) ? $node->children->all() : []),
        ] : $node;

        if (($data['type'] ?? 'condition') === 'group') {
            $result = $this->evaluate(
                $data['conditions'] ?? [],
                $snapshot,
                $log,
                $data['boolean_operator'] ?? 'all',
            );
        } else {
            $actual = Arr::get($snapshot, (string) $data['field']);
            $result = $this->compare($actual, (string) $data['operator'], $data['value'] ?? null);
        }

        $result = ($data['negated'] ?? false) ? ! $result : $result;
        $log?->__invoke($data, $result);

        return $result;
    }

    protected function equal(mixed $left, mixed $right): bool
    {
        if ($left === null || $right === null) {
            return $left === $right;
        }
        if (is_bool($left) || is_bool($right)) {
            $left = $this->boolean($left);
            $right = $this->boolean($right);

            return $left !== null && $right !== null && $left === $right;
        }
        if (is_numeric($left) && is_numeric($right)) {
            return (float) $left === (float) $right;
        }
        if ($this->date($left) && $this->date($right)) {
            return $this->date($left)?->getTimestamp() === $this->date($right)?->getTimestamp();
        }
        if (is_array($left) || is_array($right)) {
            return $this->normalizeArray((array) $left) === $this->normalizeArray((array) $right);
        }

        return $this->string($left) === $this->string($right);
    }

    protected function order(mixed $left, mixed $right): int
    {
        if (is_numeric($left) && is_numeric($right)) {
            return (float) $left <=> (float) $right;
        }
        if (($leftDate = $this->date($left)) && ($rightDate = $this->date($right))) {
            return $leftDate->getTimestamp() <=> $rightDate->getTimestamp();
        }

        return strcmp($this->string($left), $this->string($right));
    }

    protected function contains(mixed $actual, mixed $expected): bool
    {
        if ($actual === null || $expected === null) {
            return false;
        }
        if (is_array($actual)) {
            return collect($actual)->contains(fn ($item) => $this->equal($item, $expected));
        }

        return str_contains($this->string($actual), $this->string($expected));
    }

    protected function inList(mixed $actual, mixed $expected): bool
    {
        return collect(is_array($expected) ? $expected : explode(',', (string) $expected))
            ->contains(fn ($item) => $this->equal($actual, is_string($item) ? trim($item) : $item));
    }

    protected function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && $value === []);
    }

    protected function boolean(mixed $value): ?bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    }

    protected function string(mixed $value): string
    {
        return $value instanceof Stringable ? (string) $value : (is_scalar($value) ? (string) $value : json_encode($value, JSON_THROW_ON_ERROR));
    }

    protected function date(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}(?:[T ][0-9:.+-Z]+)?$/', $value)) {
            return null;
        }
        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    protected function normalizeArray(array $value): array
    {
        return array_is_list($value) ? array_values($value) : Arr::sortRecursive($value);
    }
}
