<?php

namespace Database\Factories;

use App\Models\WorkflowExecution;
use App\Models\WorkflowExecutionLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkflowExecutionLog> */
class WorkflowExecutionLogFactory extends Factory
{
    protected $model = WorkflowExecutionLog::class;

    public function definition(): array
    {
        return [
            'workflow_execution_id' => WorkflowExecution::factory(),
            'organization_id' => fn (array $attributes) => WorkflowExecution::withoutGlobalScopes()->find($attributes['workflow_execution_id'])->organization_id,
            'level' => 'info',
            'event' => 'execution_queued',
            'message' => fake()->sentence(),
            'occurred_at' => now(),
        ];
    }
}
