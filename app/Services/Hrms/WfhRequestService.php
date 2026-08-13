<?php

namespace App\Services\Hrms;

use App\Events\WfhRequestApproved;
use App\Events\WfhRequestCancelled;
use App\Events\WfhRequestRejected;
use App\Events\WfhRequestSubmitted;
use App\Models\Employee;
use App\Models\User;
use App\Models\WfhApprovalStep;
use App\Models\WfhRequest;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WfhRequestService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected WfhPolicyService $wfhPolicyService,
        protected AttendanceLockService $lockService,
        protected LeaveService $leaveService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(Employee $employee, array $data, User $actor, bool $submit = true): WfhRequest
    {
        return DB::transaction(function () use ($employee, $data, $actor, $submit): WfhRequest {
            $orgPolicy = $this->wfhPolicyService->resolveOrganizationPolicy($employee);

            if (! $orgPolicy['enabled']) {
                throw ValidationException::withMessages([
                    'work_date' => __('Work from home is not enabled for this organization.'),
                ]);
            }

            if (! in_array($orgPolicy['default_policy_type'], ['none', 'daily', 'selected_days', 'permanent'], true)) {
                throw ValidationException::withMessages([
                    'work_date' => __('Invalid organization WFH policy configuration.'),
                ]);
            }

            [$startDate, $endDate] = $this->resolveRequestRange($data);
            $this->assertEmployeeCanRequestRange($employee, $startDate, $endDate, $orgPolicy);
            $this->assertNoConflictingRequestRange($employee, $startDate, $endDate);
            $this->assertNoApprovedLeaveOverlap($employee, $startDate, $endDate);
            $this->assertRangeEditable($startDate, $endDate, isPrivileged: false);

            $requiresApproval = (bool) $orgPolicy['requires_approval'];
            $status = 'draft';
            $submittedAt = null;

            if ($submit) {
                $status = $requiresApproval ? 'pending' : 'approved';
                $submittedAt = now();
            }

            $request = WfhRequest::query()->create([
                'organization_id' => $employee->organization_id,
                'employee_id' => $employee->id,
                'work_date' => $startDate->toDateString(),
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'reason' => $data['reason'] ?? null,
                'status' => $status,
                'submitted_at' => $submittedAt,
            ]);

            if ($submit && $requiresApproval) {
                $this->createApprovalSteps($request, $employee, $orgPolicy);
                event(WfhRequestSubmitted::forModel($request, ['actor_id' => $actor->id]));
            }

            if ($submit && ! $requiresApproval) {
                event(WfhRequestApproved::forModel($request, ['actor_id' => $actor->id]));
            }

            $this->auditLogger->log($request, 'wfh_request_created', [
                'employee_id' => $employee->id,
                'work_date' => $startDate->toDateString(),
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'status' => $status,
            ], $actor);

            return $request->load(['employee', 'approvalSteps']);
        });
    }

    public function withdraw(WfhRequest $request, User $actor): WfhRequest
    {
        return DB::transaction(function () use ($request, $actor): WfhRequest {
            if (! in_array($request->status, ['draft', 'pending'], true)) {
                throw ValidationException::withMessages([
                    'status' => __('Only draft or pending WFH requests can be withdrawn.'),
                ]);
            }

            if ($request->status === 'pending') {
                $this->markPendingStepsSkipped($request);
            }

            $request->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $this->auditLogger->log($request, 'wfh_request_withdrawn', [], $actor);

            return $request->fresh(['employee', 'approvalSteps']);
        });
    }

    public function approve(WfhRequest $request, User $actor, ?string $remarks = null): WfhRequest
    {
        return DB::transaction(function () use ($request, $actor, $remarks): WfhRequest {
            if ($request->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => __('Only pending WFH requests can be approved.'),
                ]);
            }

            $isPrivileged = $actor->hasPermission('wfh.manage', $request->organization);
            $this->assertRangeEditable($request->rangeStart(), $request->rangeEnd(), isPrivileged: $isPrivileged);
            $this->assertNoApprovedLeaveOverlap($request->employee, $request->rangeStart(), $request->rangeEnd());

            $step = $this->currentPendingStep($request);
            if ($step === null) {
                throw ValidationException::withMessages([
                    'status' => __('No pending approval step found.'),
                ]);
            }

            $this->assertCanActOnStep($step, $actor);

            $step->update([
                'status' => 'approved',
                'acted_at' => now(),
                'comments' => $remarks,
            ]);

            $this->auditLogger->log($step, 'wfh_approval_step_approved', [
                'step_order' => $step->step_order,
                'wfh_request_id' => $request->id,
            ], $actor);

            $hasMorePending = $request->approvalSteps()
                ->where('status', 'pending')
                ->exists();

            if (! $hasMorePending) {
                $request->update(['status' => 'approved']);
                $this->auditLogger->log($request, 'wfh_request_approved', [
                    'work_date' => $request->rangeStart()->toDateString(),
                    'start_date' => $request->rangeStart()->toDateString(),
                    'end_date' => $request->rangeEnd()->toDateString(),
                ], $actor);
                event(WfhRequestApproved::forModel($request, ['actor_id' => $actor->id]));
            }

            return $request->fresh(['employee', 'approvalSteps']);
        });
    }

    public function reject(WfhRequest $request, User $actor, ?string $remarks = null): WfhRequest
    {
        return DB::transaction(function () use ($request, $actor, $remarks): WfhRequest {
            if ($request->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => __('Only pending WFH requests can be rejected.'),
                ]);
            }

            $step = $this->currentPendingStep($request);
            if ($step === null) {
                throw ValidationException::withMessages([
                    'status' => __('No pending approval step found.'),
                ]);
            }

            $this->assertCanActOnStep($step, $actor);

            $step->update([
                'status' => 'rejected',
                'acted_at' => now(),
                'comments' => $remarks,
            ]);

            $this->markRemainingStepsSkipped($request, $step->step_order);
            $request->update(['status' => 'rejected']);

            $this->auditLogger->log($request, 'wfh_request_rejected', [
                'remarks' => $remarks,
            ], $actor);

            event(WfhRequestRejected::forModel($request, ['actor_id' => $actor->id]));

            return $request->fresh(['employee', 'approvalSteps']);
        });
    }

    public function cancel(WfhRequest $request, User $actor, ?string $remarks = null): WfhRequest
    {
        return DB::transaction(function () use ($request, $actor, $remarks): WfhRequest {
            if ($request->status !== 'approved') {
                throw ValidationException::withMessages([
                    'status' => __('Only approved WFH requests can be cancelled.'),
                ]);
            }

            $orgPolicy = $this->wfhPolicyService->resolveOrganizationPolicy($request->employee);
            $cutoffDays = (int) $orgPolicy['cancellation_cutoff_days'];
            $cutoffDate = $request->rangeStart()->copy()->subDays($cutoffDays);

            if (now()->startOfDay()->gt($cutoffDate)) {
                throw ValidationException::withMessages([
                    'work_date' => __('WFH cannot be cancelled after the cutoff date.'),
                ]);
            }

            $isPrivileged = $actor->hasPermission('wfh.manage', $request->organization);
            $this->assertRangeEditable($request->rangeStart(), $request->rangeEnd(), isPrivileged: $isPrivileged);

            $request->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $this->auditLogger->log($request, 'wfh_request_cancelled', [
                'remarks' => $remarks,
                'work_date' => $request->rangeStart()->toDateString(),
                'start_date' => $request->rangeStart()->toDateString(),
                'end_date' => $request->rangeEnd()->toDateString(),
            ], $actor);

            event(WfhRequestCancelled::forModel($request, ['actor_id' => $actor->id]));

            return $request->fresh(['employee', 'approvalSteps']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolveRequestRange(array $data): array
    {
        $hasRange = filled($data['start_date'] ?? null) || filled($data['end_date'] ?? null);
        $hasWorkDate = filled($data['work_date'] ?? null);

        if (! $hasRange && ! $hasWorkDate) {
            throw ValidationException::withMessages([
                'work_date' => __('Provide a work date or a start/end date range.'),
            ]);
        }

        $start = Carbon::parse($data['start_date'] ?? $data['work_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'] ?? $data['start_date'] ?? $data['work_date'])->startOfDay();

        if ($end->lt($start)) {
            throw ValidationException::withMessages([
                'end_date' => __('End date must be on or after the start date.'),
            ]);
        }

        $maxDays = (int) config('hrms.wfh_max_request_days', 31);
        if ($start->diffInDays($end) + 1 > $maxDays) {
            throw ValidationException::withMessages([
                'end_date' => __('WFH requests cannot exceed :days days.', ['days' => $maxDays]),
            ]);
        }

        return [$start, $end];
    }

    /**
     * @param  array<string, mixed>  $orgPolicy
     */
    protected function assertEmployeeCanRequestRange(
        Employee $employee,
        Carbon $startDate,
        Carbon $endDate,
        array $orgPolicy,
    ): void {
        if (! in_array($employee->status, config('hrms.clockable_employee_statuses', []), true)) {
            throw ValidationException::withMessages([
                'employee_id' => __('This employee cannot request WFH.'),
            ]);
        }

        $allowed = $orgPolicy['allowed_weekdays'];
        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            if ($allowed !== [] && ! in_array((int) $cursor->isoWeekday(), $allowed, true)) {
                throw ValidationException::withMessages([
                    'work_date' => __('WFH is not allowed on :date.', [
                        'date' => $cursor->toFormattedDateString(),
                    ]),
                ]);
            }

            if ($this->wfhPolicyService->resolveForDate($employee, $cursor, ignoreLeave: true)['policy_type'] === 'permanent') {
                throw ValidationException::withMessages([
                    'work_date' => __('Employee already has permanent WFH for :date.', [
                        'date' => $cursor->toFormattedDateString(),
                    ]),
                ]);
            }

            $cursor->addDay();
        }
    }

    protected function assertNoConflictingRequestRange(
        Employee $employee,
        Carbon $startDate,
        Carbon $endDate,
        ?int $ignoreId = null,
    ): void {
        $query = WfhRequest::query()
            ->where('organization_id', $employee->organization_id)
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($startDate, $endDate): void {
                $q->where(function ($inner) use ($startDate, $endDate): void {
                    // Prefer explicit range columns when present.
                    $inner->whereNotNull('start_date')
                        ->whereDate('start_date', '<=', $endDate->toDateString())
                        ->whereDate('end_date', '>=', $startDate->toDateString());
                })->orWhere(function ($inner) use ($startDate, $endDate): void {
                    // Legacy single-day rows without range columns.
                    $inner->whereNull('start_date')
                        ->whereDate('work_date', '>=', $startDate->toDateString())
                        ->whereDate('work_date', '<=', $endDate->toDateString());
                });
            });

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'work_date' => __('A WFH request already exists for one or more dates in this range.'),
            ]);
        }
    }

    protected function assertNoApprovedLeaveOverlap(Employee $employee, Carbon $startDate, Carbon $endDate): void
    {
        if ($this->leaveService->getApprovedLeaveForDateRange($employee, $startDate, $endDate)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'work_date' => __('Cannot request WFH while approved leave overlaps this date range.'),
            ]);
        }
    }

    protected function assertRangeEditable(Carbon $startDate, Carbon $endDate, bool $isPrivileged): void
    {
        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            $this->lockService->assertEditable($cursor, isPrivileged: $isPrivileged);
            $cursor->addDay();
        }
    }

    /**
     * @param  array<string, mixed>  $orgPolicy
     */
    protected function createApprovalSteps(WfhRequest $request, Employee $employee, array $orgPolicy): void
    {
        $steps = [];
        $order = 1;

        $manager = $employee->reportingManager;
        $steps[] = [
            'organization_id' => $request->organization_id,
            'wfh_request_id' => $request->id,
            'step_order' => $order++,
            'approver_employee_id' => $manager?->id,
            'approver_user_id' => $manager?->user_id,
            'status' => 'pending',
        ];

        if ($orgPolicy['requires_hr_approval']) {
            $steps[] = [
                'organization_id' => $request->organization_id,
                'wfh_request_id' => $request->id,
                'step_order' => $order,
                'approver_employee_id' => null,
                'approver_user_id' => null,
                'status' => 'pending',
            ];
        }

        foreach ($steps as $step) {
            WfhApprovalStep::query()->create($step);
        }
    }

    protected function currentPendingStep(WfhRequest $request): ?WfhApprovalStep
    {
        return $request->approvalSteps()
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->first();
    }

    protected function assertCanActOnStep(WfhApprovalStep $step, User $actor): void
    {
        if ($step->approver_user_id !== null && (int) $step->approver_user_id === (int) $actor->id) {
            return;
        }

        if ($step->approver_employee_id === null && $step->approver_user_id === null) {
            if ($actor->hasPermission('wfh.manage', $step->organization)) {
                return;
            }
        }

        if ($step->approver_employee_id !== null) {
            $approverEmployee = Employee::query()->find($step->approver_employee_id);
            if ($approverEmployee !== null && (int) $approverEmployee->user_id === (int) $actor->id) {
                return;
            }
        }

        if ($actor->hasPermission('wfh.manage', $step->organization)) {
            return;
        }

        throw ValidationException::withMessages([
            'approver' => __('You are not authorized to act on this approval step.'),
        ]);
    }

    protected function markPendingStepsSkipped(WfhRequest $request): void
    {
        $request->approvalSteps()
            ->where('status', 'pending')
            ->update(['status' => 'skipped', 'acted_at' => now()]);
    }

    protected function markRemainingStepsSkipped(WfhRequest $request, int $afterStepOrder): void
    {
        $request->approvalSteps()
            ->where('status', 'pending')
            ->where('step_order', '>', $afterStepOrder)
            ->update(['status' => 'skipped', 'acted_at' => now()]);
    }
}
