<?php

namespace Database\Factories;

use App\Models\Workflow;
use App\Models\WorkflowCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkflowCondition> */
class WorkflowConditionFactory extends Factory
{
    protected $model = WorkflowCondition::class;

    public function definition(): array
    {
        return [
            'workflow_id' => Workflow::factory(),
            'organization_id' => fn (array $attributes) => Workflow::withoutGlobalScopes()->find($attributes['workflow_id'])->organization_id,
            'type' => WorkflowCondition::TYPE_CONDITION,
            'field' => 'status',
            'operator' => 'equals',
            'value' => ['value' => 'new'],
            'position' => 0,
        ];
    }
}
