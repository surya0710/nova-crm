<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\ResourceAllocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ResourceAllocation> */
class ResourceAllocationFactory extends Factory
{
    protected $model = ResourceAllocation::class;

    public function definition(): array
    {
        $start = now()->startOfWeek();

        return [
            'organization_id' => Organization::factory(),
            'employee_id' => Employee::factory(),
            'project_id' => null,
            'task_id' => null,
            'allocation_type' => 'internal',
            'allocation_percentage' => fake()->numberBetween(10, 100),
            'planned_hours' => null,
            'planned_start_date' => $start->toDateString(),
            'planned_end_date' => $start->copy()->addDays(4)->toDateString(),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ResourceAllocation $allocation) {
            $this->alignOrganization($allocation);
        })->afterCreating(function (ResourceAllocation $allocation) {
            $this->alignOrganization($allocation, true);
        });
    }

    protected function alignOrganization(ResourceAllocation $allocation, bool $persist = false): void
    {
        $employee = $allocation->employee_id
            ? Employee::query()->find($allocation->employee_id)
            : null;

        if ($employee && (int) $allocation->organization_id !== (int) $employee->organization_id) {
            $allocation->organization_id = $employee->organization_id;

            if ($persist) {
                $allocation->saveQuietly();
            }
        }
    }

    public function forProject(int $projectId): static
    {
        return $this->state(fn () => [
            'allocation_type' => 'project',
            'project_id' => $projectId,
            'task_id' => null,
        ]);
    }

    public function forTask(int $projectId, int $taskId): static
    {
        return $this->state(fn () => [
            'allocation_type' => 'task',
            'project_id' => $projectId,
            'task_id' => $taskId,
        ]);
    }
}
