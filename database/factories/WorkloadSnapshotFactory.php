<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\WorkloadSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkloadSnapshot> */
class WorkloadSnapshotFactory extends Factory
{
    protected $model = WorkloadSnapshot::class;

    public function definition(): array
    {
        $available = (float) config('resources.default_working_hours_per_day', 8);
        $allocated = fake()->randomFloat(2, 0, $available * 1.2);
        $utilization = $available > 0 ? round(($allocated / $available) * 100, 2) : 0;

        return [
            'organization_id' => Organization::factory(),
            'employee_id' => Employee::factory(),
            'snapshot_date' => now()->toDateString(),
            'allocated_hours' => $allocated,
            'available_hours' => $available,
            'utilization_percentage' => $utilization,
            'overall_status' => $this->statusForUtilization($utilization),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (WorkloadSnapshot $snapshot) {
            $this->alignOrganization($snapshot);
        })->afterCreating(function (WorkloadSnapshot $snapshot) {
            $this->alignOrganization($snapshot, true);
        });
    }

    protected function alignOrganization(WorkloadSnapshot $snapshot, bool $persist = false): void
    {
        $employee = $snapshot->employee_id
            ? Employee::query()->find($snapshot->employee_id)
            : null;

        if ($employee && (int) $snapshot->organization_id !== (int) $employee->organization_id) {
            $snapshot->organization_id = $employee->organization_id;

            if ($persist) {
                $snapshot->saveQuietly();
            }
        }
    }

    protected function statusForUtilization(float $utilization): string
    {
        $under = (float) config('resources.underutilization_threshold', 50);
        $over = (float) config('resources.overallocation_threshold', 100);

        if ($utilization < $under) {
            return 'underutilized';
        }

        if ($utilization > $over) {
            return 'overallocated';
        }

        return 'optimal';
    }
}
