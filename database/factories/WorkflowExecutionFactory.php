<?php

namespace Database\Factories;

use App\Models\Workflow;
use App\Models\WorkflowExecution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<WorkflowExecution> */
class WorkflowExecutionFactory extends Factory
{
    protected $model = WorkflowExecution::class;

    public function definition(): array
    {
        return [
            'workflow_id' => Workflow::factory(),
            'organization_id' => fn (array $attributes) => Workflow::withoutGlobalScopes()->find($attributes['workflow_id'])->organization_id,
            'workflow_version' => 1,
            'status' => WorkflowExecution::STATUS_PENDING,
            'idempotency_key' => (string) Str::uuid(),
            'attempt' => 0,
            'current_action_position' => 0,
            'queued_at' => now(),
        ];
    }
}
