<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Workflow> */
class WorkflowFactory extends Factory
{
    protected $model = Workflow::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'trigger_type' => 'lead.created',
            'trigger_config' => [],
            'status' => Workflow::STATUS_DRAFT,
            'version' => 1,
            'concurrency_limit' => 1,
            'execution_timeout_seconds' => 300,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => Workflow::STATUS_ACTIVE, 'enabled_at' => now()]);
    }
}
