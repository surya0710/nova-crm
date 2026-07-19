<?php

namespace Database\Factories;

use App\Models\Workflow;
use App\Models\WorkflowAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkflowAction> */
class WorkflowActionFactory extends Factory
{
    protected $model = WorkflowAction::class;

    public function definition(): array
    {
        return [
            'workflow_id' => Workflow::factory(),
            'organization_id' => fn (array $attributes) => Workflow::withoutGlobalScopes()->find($attributes['workflow_id'])->organization_id,
            'type' => 'update_record',
            'configuration' => ['field' => 'status', 'value' => 'contacted'],
            'status' => WorkflowAction::STATUS_ACTIVE,
            'position' => 0,
        ];
    }
}
