<?php

namespace Tests\Unit;

use App\Workflow\ConditionEvaluator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WorkflowConditionEvaluatorTest extends TestCase
{
    #[DataProvider('comparisons')]
    public function test_operators_are_deterministic(mixed $actual, string $operator, mixed $expected, bool $result): void
    {
        $this->assertSame($result, (new ConditionEvaluator)->compare($actual, $operator, $expected));
    }

    public static function comparisons(): array
    {
        return [
            [null, 'equals', null, true],
            ['1', 'equals', 1, true],
            ['new', 'not_equals', 'closed', true],
            ['new', 'not_equals', 'new', false],
            ['true', 'equals', true, true],
            ['2026-01-01', 'less_than', '2026-01-02', true],
            [['sales', 'vip'], 'contains', 'vip', true],
            ['Nova CRM', 'does_not_contain', 'ERP', true],
            ['Nova CRM', 'starts_with', 'Nova', true],
            ['Nova CRM', 'ends_with', 'CRM', true],
            [10, 'greater_than', 9, true],
            [10, 'greater_than_equal', 10, true],
            [9, 'less_than_equal', 10, true],
            [10, 'between', [5, 15], true],
            ['sales', 'in_list', ['support', 'sales'], true],
            ['sales', 'not_in_list', ['support'], true],
            [[], 'empty', null, true],
            [false, 'not_empty', null, true],
            [null, 'contains', '', false],
            [null, 'greater_than', -1, false],
            [true, 'equals', 'not-a-boolean', false],
            [10, 'between', [15, 5], false],
            ['Nova', 'equals', 'nova', false],
        ];
    }

    public function test_nested_groups_and_dot_paths_are_entity_agnostic(): void
    {
        $conditions = [[
            'type' => 'group',
            'boolean_operator' => 'all',
            'conditions' => [
                ['type' => 'condition', 'field' => 'custom_fields.score', 'operator' => 'greater_than_equal', 'value' => 80],
                [
                    'type' => 'group',
                    'boolean_operator' => 'any',
                    'conditions' => [
                        ['type' => 'condition', 'field' => 'metadata.region', 'operator' => 'equals', 'value' => 'west'],
                        ['type' => 'condition', 'field' => 'tags', 'operator' => 'contains', 'value' => 'vip'],
                    ],
                ],
            ],
        ]];

        $snapshot = ['custom_fields' => ['score' => 85], 'metadata' => ['region' => 'east'], 'tags' => ['vip']];

        $this->assertTrue((new ConditionEvaluator)->evaluate($conditions, $snapshot));
    }

    public function test_leaf_and_group_negation_invert_the_completed_result(): void
    {
        $evaluator = new ConditionEvaluator;

        $this->assertTrue($evaluator->evaluate([[
            'type' => 'condition',
            'field' => 'status',
            'operator' => 'equals',
            'value' => 'closed',
            'negated' => true,
        ]], ['status' => 'new']));

        $this->assertFalse($evaluator->evaluate([[
            'type' => 'group',
            'boolean_operator' => 'any',
            'negated' => true,
            'conditions' => [[
                'type' => 'condition',
                'field' => 'status',
                'operator' => 'equals',
                'value' => 'new',
            ]],
        ]], ['status' => 'new']));
    }
}
