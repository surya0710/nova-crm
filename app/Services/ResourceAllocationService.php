<?php

namespace App\Services;

use App\Events\CapacityExceeded;
use App\Events\OverallocationDetected;
use App\Events\ResourceAllocated;
use App\Events\ResourceAllocationUpdated;
use App\Events\ResourceReleased;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ResourceAllocation;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Workflow\WorkflowRuntimeContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class ResourceAllocationService
{
    public function __construct(
        protected ?WorkloadService $workloadService = null,
        protected ?MetadataEntityFormService $metadataForms = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor, array $metadataValues = []): ResourceAllocation
    {
        return DB::transaction(function () use ($data, $actor, $metadataValues) {
            $organizationId = (int) ($data['organization_id'] ?? app(TenantContext::class)->id());
            $payload = $this->validateAndNormalize($data, $organizationId);

            $allocation = ResourceAllocation::query()->create([
                ...$payload,
                'created_by' => $actor->id,
            ]);

            $this->persistMetadata($allocation, $metadataValues);
            $allocation = $allocation->fresh(['employee.user', 'project', 'task']);

            $runtime = app(WorkflowRuntimeContext::class);
            event(ResourceAllocated::forModel(
                $allocation,
                ['actor_id' => $actor->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            $this->notifyAssigned($allocation, $actor);
            $this->detectAndFireCapacityEvents($allocation, $actor);

            return $allocation;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ResourceAllocation $allocation, array $data, User $actor, array $metadataValues = []): ResourceAllocation
    {
        return DB::transaction(function () use ($allocation, $data, $actor, $metadataValues) {
            $payload = $this->validateAndNormalize(
                [...$allocation->only([
                    'employee_id',
                    'project_id',
                    'task_id',
                    'allocation_type',
                    'allocation_percentage',
                    'planned_hours',
                    'planned_start_date',
                    'planned_end_date',
                    'notes',
                ]), ...$data],
                (int) $allocation->organization_id,
                $allocation->id,
            );

            $allocation->update($payload);
            $this->persistMetadata($allocation, $metadataValues);
            $allocation = $allocation->fresh(['employee.user', 'project', 'task']);

            $runtime = app(WorkflowRuntimeContext::class);
            event(ResourceAllocationUpdated::forModel(
                $allocation,
                ['actor_id' => $actor->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            $this->detectAndFireCapacityEvents($allocation, $actor);

            return $allocation;
        });
    }

    public function delete(ResourceAllocation $allocation, User $actor): void
    {
        DB::transaction(function () use ($allocation, $actor) {
            $allocation = $allocation->fresh(['employee.user', 'project']) ?? $allocation;

            $runtime = app(WorkflowRuntimeContext::class);
            event(ResourceReleased::forModel(
                $allocation,
                ['actor_id' => $actor->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            $allocation->delete();
        });
    }

    /**
     * @return Collection<int, ResourceAllocation>
     */
    public function allocationsForEmployee(Employee $employee, Carbon $from, Carbon $to): Collection
    {
        return ResourceAllocation::query()
            ->where('organization_id', $employee->organization_id)
            ->where('employee_id', $employee->id)
            ->whereDate('planned_start_date', '<=', $to->toDateString())
            ->whereDate('planned_end_date', '>=', $from->toDateString())
            ->orderBy('planned_start_date')
            ->get();
    }

    /**
     * Sum of allocation percentages covering a specific date for an employee.
     */
    public function allocationPercentageOnDate(Employee $employee, Carbon $date, ?int $excludeId = null): int
    {
        return (int) ResourceAllocation::query()
            ->where('organization_id', $employee->organization_id)
            ->where('employee_id', $employee->id)
            ->when($excludeId, fn ($q) => $q->whereKeyNot($excludeId))
            ->whereDate('planned_start_date', '<=', $date->toDateString())
            ->whereDate('planned_end_date', '>=', $date->toDateString())
            ->sum('allocation_percentage');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function validateAndNormalize(array $data, int $organizationId, ?int $ignoreId = null): array
    {
        $employee = Employee::query()
            ->withoutGlobalScopes()
            ->whereKey((int) ($data['employee_id'] ?? 0))
            ->first();

        if (! $employee || (int) $employee->organization_id !== $organizationId) {
            throw ValidationException::withMessages([
                'employee_id' => __('The selected employee does not belong to this organization.'),
            ]);
        }

        $type = (string) ($data['allocation_type'] ?? '');
        $types = config('resources.allocation_types', []);

        if (! array_key_exists($type, $types)) {
            throw ValidationException::withMessages([
                'allocation_type' => __('Invalid allocation type.'),
            ]);
        }

        $projectId = isset($data['project_id']) && $data['project_id'] !== '' && $data['project_id'] !== null
            ? (int) $data['project_id']
            : null;
        $taskId = isset($data['task_id']) && $data['task_id'] !== '' && $data['task_id'] !== null
            ? (int) $data['task_id']
            : null;

        if (in_array($type, ['project', 'task'], true) && ! $projectId) {
            throw ValidationException::withMessages([
                'project_id' => __('A project is required for :type allocations.', ['type' => $type]),
            ]);
        }

        $project = null;
        if ($projectId) {
            $project = Project::query()
                ->withoutGlobalScopes()
                ->whereKey($projectId)
                ->first();

            if (! $project || (int) $project->organization_id !== $organizationId) {
                throw ValidationException::withMessages([
                    'project_id' => __('The selected project does not belong to this organization.'),
                ]);
            }

            if ($project->isArchived()) {
                throw ValidationException::withMessages([
                    'project_id' => __('Archived projects cannot receive allocations.'),
                ]);
            }
        }

        if ($type === 'task') {
            if (! $taskId) {
                throw ValidationException::withMessages([
                    'task_id' => __('A task is required for task allocations.'),
                ]);
            }

            $task = Task::query()
                ->withoutGlobalScopes()
                ->whereKey($taskId)
                ->first();

            if (! $task || (int) $task->organization_id !== $organizationId) {
                throw ValidationException::withMessages([
                    'task_id' => __('The selected task does not belong to this organization.'),
                ]);
            }

            if ((int) $task->project_id !== (int) $projectId) {
                throw ValidationException::withMessages([
                    'task_id' => __('The task does not belong to the selected project.'),
                ]);
            }

            $isNewOrRetargeted = $ignoreId === null
                || (int) (ResourceAllocation::query()->whereKey($ignoreId)->value('task_id') ?? 0) !== (int) $taskId;

            if ($isNewOrRetargeted && ! $task->isOpen()) {
                throw ValidationException::withMessages([
                    'task_id' => __('Completed, closed, or archived tasks cannot receive new allocations.'),
                ]);
            }
        } elseif ($taskId) {
            throw ValidationException::withMessages([
                'task_id' => __('Task may only be set for task allocations.'),
            ]);
        }

        $percentage = (int) ($data['allocation_percentage'] ?? 0);
        $max = (int) config('resources.max_allocation_percentage', 100);

        if ($percentage < 1 || $percentage > $max) {
            throw ValidationException::withMessages([
                'allocation_percentage' => __('Allocation percentage must be between 1 and :max.', ['max' => $max]),
            ]);
        }

        $start = Carbon::parse($data['planned_start_date'] ?? now())->startOfDay();
        $end = Carbon::parse($data['planned_end_date'] ?? $start)->startOfDay();

        if ($end->lt($start)) {
            throw ValidationException::withMessages([
                'planned_end_date' => __('Planned end date must be on or after the start date.'),
            ]);
        }

        $this->assertOverlappingCapacity($employee, $start, $end, $percentage, $max, $ignoreId);

        return [
            'organization_id' => $organizationId,
            'employee_id' => $employee->id,
            'project_id' => $projectId,
            'task_id' => $type === 'task' ? $taskId : null,
            'allocation_type' => $type,
            'allocation_percentage' => $percentage,
            'planned_hours' => isset($data['planned_hours']) && $data['planned_hours'] !== '' && $data['planned_hours'] !== null
                ? (float) $data['planned_hours']
                : null,
            'planned_start_date' => $start->toDateString(),
            'planned_end_date' => $end->toDateString(),
            'notes' => $data['notes'] ?? null,
        ];
    }

    protected function assertOverlappingCapacity(
        Employee $employee,
        Carbon $start,
        Carbon $end,
        int $percentage,
        int $max,
        ?int $ignoreId = null,
    ): void {
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $existing = $this->allocationPercentageOnDate($employee, $cursor, $ignoreId);
            $total = $existing + $percentage;

            if ($total > $max) {
                throw ValidationException::withMessages([
                    'allocation_percentage' => __(
                        'Overlapping allocations exceed :max% on :date (would be :total%). Concurrent allocations are allowed only when their percentages sum to at most :max on each overlapping day.',
                        [
                            'max' => $max,
                            'date' => $cursor->toDateString(),
                            'total' => $total,
                        ]
                    ),
                ]);
            }

            $cursor->addDay();
        }
    }

    protected function detectAndFireCapacityEvents(ResourceAllocation $allocation, User $actor): void
    {
        $employee = $allocation->employee;
        if (! $employee) {
            return;
        }

        $from = $allocation->planned_start_date->copy();
        $to = $allocation->planned_end_date->copy();
        $result = $this->workload()->calculateForEmployee($employee, $from, $to);

        if (($result['status'] ?? null) !== 'overallocated') {
            return;
        }

        $runtime = app(WorkflowRuntimeContext::class);
        $payload = [
            'actor_id' => $actor->id,
            'employee_id' => $employee->id,
            'utilization_percentage' => $result['utilization'] ?? null,
            'allocated_hours' => $result['allocated'] ?? null,
            'available_hours' => $result['available'] ?? null,
        ];

        event(CapacityExceeded::forModel(
            $allocation,
            $payload,
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        event(OverallocationDetected::forModel(
            $allocation,
            $payload,
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        $user = $employee->user;
        if ($user && $user->id !== $actor->id) {
            $user->notify(new CrmNotification(
                title: __('Capacity exceeded'),
                message: __('Your planned allocations exceed available capacity (:utilization%).', [
                    'utilization' => number_format((float) ($result['utilization'] ?? 0), 1),
                ]),
                actionUrl: $this->resourceUrl(),
                organizationId: (int) $allocation->organization_id,
            ));

            $user->notify(new CrmNotification(
                title: __('Resource conflict'),
                message: __('A resource conflict was detected for your workload between :from and :to.', [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ]),
                actionUrl: $this->resourceUrl(),
                organizationId: (int) $allocation->organization_id,
            ));
        }
    }

    protected function notifyAssigned(ResourceAllocation $allocation, User $actor): void
    {
        $user = $allocation->employee?->user;
        if (! $user || $user->id === $actor->id) {
            return;
        }

        $projectName = $allocation->project?->name;
        $title = $projectName
            ? __('Assigned to project')
            : __('Resource allocated');
        $message = $projectName
            ? __('You were allocated to :project (:percentage%).', [
                'project' => $projectName,
                'percentage' => $allocation->allocation_percentage,
            ])
            : __('You were allocated :percentage% for :type from :from to :to.', [
                'percentage' => $allocation->allocation_percentage,
                'type' => $allocation->allocation_type_label,
                'from' => $allocation->planned_start_date->toDateString(),
                'to' => $allocation->planned_end_date->toDateString(),
            ]);

        $user->notify(new CrmNotification(
            title: $title,
            message: $message,
            actionUrl: $projectName && $allocation->project_id
                ? $this->projectUrl($allocation->project)
                : $this->resourceUrl(),
            organizationId: (int) $allocation->organization_id,
        ));
    }

    protected function projectUrl(?Project $project): ?string
    {
        if (! $project || ! Route::has('projects.show')) {
            return null;
        }

        return route('projects.show', $project);
    }

    protected function resourceUrl(?ResourceAllocation $allocation = null): ?string
    {
        if ($allocation && Route::has('resources.allocations.show')) {
            return route('resources.allocations.show', $allocation);
        }

        if (Route::has('resources.planner')) {
            return route('resources.planner');
        }

        return null;
    }

    /**
     * Notify employees whose allocations end within the next N days.
     *
     * Intended for scheduled or forecast-risk sweeps; safe to call repeatedly.
     *
     * @return int Number of notifications sent
     */
    public function notifyEndingSoon(?int $withinDays = null): int
    {
        $withinDays ??= (int) config('resources.capacity_risk_days', 14);
        $from = now()->startOfDay();
        $to = now()->copy()->addDays(max(1, $withinDays))->endOfDay();
        $sent = 0;

        $allocations = ResourceAllocation::query()
            ->with(['employee.user', 'project'])
            ->whereDate('planned_end_date', '>=', $from->toDateString())
            ->whereDate('planned_end_date', '<=', $to->toDateString())
            ->get();

        foreach ($allocations as $allocation) {
            $user = $allocation->employee?->user;
            if (! $user) {
                continue;
            }

            $user->notify(new CrmNotification(
                title: __('Allocation ending soon'),
                message: __('Your :type allocation ends on :date.', [
                    'type' => $allocation->allocation_type_label,
                    'date' => $allocation->planned_end_date->toDateString(),
                ]),
                actionUrl: $this->resourceUrl($allocation),
                organizationId: (int) $allocation->organization_id,
            ));
            $sent++;
        }

        return $sent;
    }

    /**
     * @return array{changed?: bool}|null
     */
    protected function persistMetadata(ResourceAllocation $allocation, array $metadataValues): ?array
    {
        if ($metadataValues === [] || ! $this->metadataForms()) {
            return null;
        }

        return $this->metadataForms()->persistValidatedValues($allocation, $metadataValues);
    }

    protected function metadataForms(): ?MetadataEntityFormService
    {
        if ($this->metadataForms !== null) {
            return $this->metadataForms;
        }

        if (! class_exists(MetadataEntityFormService::class)) {
            return null;
        }

        return $this->metadataForms = app(MetadataEntityFormService::class);
    }

    protected function workload(): WorkloadService
    {
        return $this->workloadService ??= app(WorkloadService::class);
    }
}
