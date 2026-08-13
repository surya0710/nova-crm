<?php

namespace App\Services\Hrms;

use App\Models\Employee;
use App\Models\EmployeeWfhAssignment;
use App\Models\Organization;
use App\Models\User;
use App\Models\WfhRequest;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WfhPolicyService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected LeaveService $leaveService,
    ) {}

    /**
     * @return array{
     *     enabled: bool,
     *     default_policy_type: string,
     *     requires_approval: bool,
     *     requires_hr_approval: bool,
     *     bypass_geofence: bool,
     *     record_gps_when_wfh: bool,
     *     allowed_weekdays: array<int, int>,
     *     cancellation_cutoff_days: int
     * }
     */
    public function resolveOrganizationPolicy(Organization|Employee $subject): array
    {
        $organization = $subject instanceof Employee
            ? ($subject->organization ?? Organization::query()->find($subject->organization_id))
            : $subject;

        $settings = $organization?->settings['wfh_policies'] ?? [];
        $defaultType = (string) ($settings['default_policy_type']
            ?? config('hrms.wfh_default_policy_type', 'none'));

        if (! array_key_exists($defaultType, config('hrms.wfh_policy_types', []))) {
            $defaultType = 'none';
        }

        $allowedWeekdays = $settings['allowed_weekdays']
            ?? config('hrms.wfh_default_allowed_weekdays', [1, 2, 3, 4, 5]);

        return [
            'enabled' => (bool) ($settings['enabled'] ?? config('hrms.wfh_enabled_default', false)),
            'default_policy_type' => $defaultType,
            'requires_approval' => (bool) ($settings['requires_approval'] ?? true),
            'requires_hr_approval' => (bool) ($settings['requires_hr_approval'] ?? false),
            'bypass_geofence' => (bool) ($settings['bypass_geofence'] ?? true),
            'record_gps_when_wfh' => (bool) ($settings['record_gps_when_wfh'] ?? false),
            'allowed_weekdays' => array_values(array_map('intval', (array) $allowedWeekdays)),
            'cancellation_cutoff_days' => (int) ($settings['cancellation_cutoff_days']
                ?? config('hrms.wfh_cancellation_cutoff_days', 0)),
        ];
    }

    /**
     * Deterministic WFH resolution for a date.
     *
     * Precedence (highest first):
     * 1. approved daily request
     * 2. permanent assignment
     * 3. selected_days assignment matching weekday
     * 4. none
     *
     * @return array{
     *     is_wfh: bool,
     *     policy_type: string,
     *     source: ?string,
     *     source_id: ?int,
     *     bypass_geofence: bool,
     *     record_gps: bool,
     *     organization_policy: array<string, mixed>
     * }
     */
    public function resolveForDate(
        Employee $employee,
        Carbon|string|null $date = null,
        bool $ignoreLeave = false,
    ): array {
        $day = $date === null ? now()->startOfDay() : Carbon::parse($date)->startOfDay();
        $orgPolicy = $this->resolveOrganizationPolicy($employee);

        $base = [
            'is_wfh' => false,
            'policy_type' => 'none',
            'source' => null,
            'source_id' => null,
            'bypass_geofence' => false,
            'record_gps' => false,
            'organization_policy' => $orgPolicy,
            'suppressed_by_leave' => false,
        ];

        if (! $orgPolicy['enabled']) {
            return $base;
        }

        // Approved leave always outranks WFH activation / geofence exemption.
        if (! $ignoreLeave && $this->leaveService->getApprovedLeaveForDate($employee, $day)->isNotEmpty()) {
            $base['suppressed_by_leave'] = true;

            return $base;
        }

        $daily = $this->findApprovedDailyRequest($employee, $day);
        if ($daily !== null) {
            return $this->activeResult('daily', 'request', $daily->id, $orgPolicy);
        }

        $permanent = $this->findActiveAssignment($employee, $day, 'permanent');
        if ($permanent !== null) {
            return $this->activeResult('permanent', 'assignment', $permanent->id, $orgPolicy);
        }

        $selected = $this->findActiveAssignment($employee, $day, 'selected_days');
        if ($selected !== null && $selected->matchesWeekday($day)) {
            return $this->activeResult('selected_days', 'assignment', $selected->id, $orgPolicy);
        }

        return $base;
    }

    public function isWfhDay(Employee $employee, Carbon|string|null $date = null): bool
    {
        return $this->resolveForDate($employee, $date)['is_wfh'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function assign(Employee $employee, array $data, User $actor): EmployeeWfhAssignment
    {
        return DB::transaction(function () use ($employee, $data, $actor): EmployeeWfhAssignment {
            $orgPolicy = $this->resolveOrganizationPolicy($employee);
            if (! $orgPolicy['enabled']) {
                throw ValidationException::withMessages([
                    'policy_type' => __('Work from home is not enabled for this organization.'),
                ]);
            }

            $policyType = (string) ($data['policy_type'] ?? '');
            if (! in_array($policyType, ['permanent', 'selected_days'], true)) {
                throw ValidationException::withMessages([
                    'policy_type' => __('WFH assignment policy type must be permanent or selected_days.'),
                ]);
            }

            $effectiveFrom = Carbon::parse($data['effective_from'])->startOfDay();
            $effectiveTo = filled($data['effective_to'] ?? null)
                ? Carbon::parse($data['effective_to'])->startOfDay()
                : null;

            if ($effectiveTo !== null && $effectiveTo->lt($effectiveFrom)) {
                throw ValidationException::withMessages([
                    'effective_to' => __('Effective end date must be on or after the start date.'),
                ]);
            }

            $weekdays = null;
            if ($policyType === 'selected_days') {
                $weekdays = array_values(array_unique(array_map('intval', (array) ($data['weekdays'] ?? []))));
                $this->assertValidWeekdays($weekdays, $orgPolicy['allowed_weekdays']);
            }

            $this->assertNoOverlappingAssignment(
                $employee,
                $effectiveFrom,
                $effectiveTo,
                $policyType,
            );

            $assignment = EmployeeWfhAssignment::query()->create([
                'organization_id' => $employee->organization_id,
                'employee_id' => $employee->id,
                'policy_type' => $policyType,
                'weekdays' => $weekdays,
                'effective_from' => $effectiveFrom->toDateString(),
                'effective_to' => $effectiveTo?->toDateString(),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'reason' => $data['reason'] ?? null,
                'assigned_by' => $actor->id,
            ]);

            $this->auditLogger->log($assignment, 'wfh_assignment_created', [
                'employee_id' => $employee->id,
                'policy_type' => $policyType,
                'effective_from' => $assignment->effective_from?->toDateString(),
                'effective_to' => $assignment->effective_to?->toDateString(),
                'weekdays' => $weekdays,
            ], $actor);

            return $assignment->load(['employee', 'assignedBy']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAssignment(EmployeeWfhAssignment $assignment, array $data, User $actor): EmployeeWfhAssignment
    {
        return DB::transaction(function () use ($assignment, $data, $actor): EmployeeWfhAssignment {
            $orgPolicy = $this->resolveOrganizationPolicy($assignment->employee);
            $policyType = (string) ($data['policy_type'] ?? $assignment->policy_type);

            if (! in_array($policyType, ['permanent', 'selected_days'], true)) {
                throw ValidationException::withMessages([
                    'policy_type' => __('WFH assignment policy type must be permanent or selected_days.'),
                ]);
            }

            $effectiveFrom = Carbon::parse($data['effective_from'] ?? $assignment->effective_from)->startOfDay();
            $effectiveTo = array_key_exists('effective_to', $data)
                ? (filled($data['effective_to']) ? Carbon::parse($data['effective_to'])->startOfDay() : null)
                : $assignment->effective_to?->copy()->startOfDay();

            if ($effectiveTo !== null && $effectiveTo->lt($effectiveFrom)) {
                throw ValidationException::withMessages([
                    'effective_to' => __('Effective end date must be on or after the start date.'),
                ]);
            }

            $weekdays = $assignment->weekdays;
            if ($policyType === 'selected_days') {
                $weekdays = array_values(array_unique(array_map(
                    'intval',
                    (array) ($data['weekdays'] ?? $assignment->weekdays ?? [])
                )));
                $this->assertValidWeekdays($weekdays, $orgPolicy['allowed_weekdays']);
            } else {
                $weekdays = null;
            }

            $this->assertNoOverlappingAssignment(
                $assignment->employee,
                $effectiveFrom,
                $effectiveTo,
                $policyType,
                $assignment->id,
            );

            $before = $assignment->only([
                'policy_type', 'weekdays', 'effective_from', 'effective_to', 'is_active', 'reason',
            ]);

            $assignment->update([
                'policy_type' => $policyType,
                'weekdays' => $weekdays,
                'effective_from' => $effectiveFrom->toDateString(),
                'effective_to' => $effectiveTo?->toDateString(),
                'is_active' => array_key_exists('is_active', $data)
                    ? (bool) $data['is_active']
                    : $assignment->is_active,
                'reason' => $data['reason'] ?? $assignment->reason,
            ]);

            $this->auditLogger->log($assignment, 'wfh_assignment_updated', [
                'before' => $before,
                'after' => $assignment->only(array_keys($before)),
            ], $actor);

            return $assignment->fresh(['employee', 'assignedBy']);
        });
    }

    public function endAssignment(
        EmployeeWfhAssignment $assignment,
        User $actor,
        Carbon|string|null $endedOn = null,
        ?string $reason = null,
    ): EmployeeWfhAssignment {
        return DB::transaction(function () use ($assignment, $actor, $endedOn, $reason): EmployeeWfhAssignment {
            $endDate = $endedOn === null
                ? now()->startOfDay()
                : Carbon::parse($endedOn)->startOfDay();

            if ($endDate->lt($assignment->effective_from->copy()->startOfDay())) {
                throw ValidationException::withMessages([
                    'effective_to' => __('End date cannot be before the assignment start date.'),
                ]);
            }

            $assignment->update([
                'effective_to' => $endDate->toDateString(),
                'is_active' => false,
                'reason' => $reason ?? $assignment->reason,
            ]);

            $this->auditLogger->log($assignment, 'wfh_assignment_ended', [
                'effective_to' => $endDate->toDateString(),
                'reason' => $reason,
            ], $actor);

            return $assignment->fresh(['employee', 'assignedBy']);
        });
    }

    /** @return Collection<int, EmployeeWfhAssignment> */
    public function assignmentsForEmployee(Employee $employee): Collection
    {
        return EmployeeWfhAssignment::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('is_active')
            ->orderByDesc('effective_from')
            ->get();
    }

    /**
     * Invalidate WFH state when an employee moves organizations.
     *
     * Branch changes do not end WFH (assignments are org-scoped; geofence
     * re-resolves against the new branch). Organization changes end active
     * assignments and cancel open/future requests in the prior tenant.
     *
     * @return array{ended_assignments: int, cancelled_requests: int}
     */
    public function handleEmployeeOrganizationTransfer(
        Employee $employee,
        int $previousOrganizationId,
        User $actor,
    ): array {
        return DB::transaction(function () use ($employee, $previousOrganizationId, $actor): array {
            $ended = 0;
            $cancelled = 0;

            $assignments = EmployeeWfhAssignment::withoutGlobalScopes()
                ->where('organization_id', $previousOrganizationId)
                ->where('employee_id', $employee->id)
                ->where('is_active', true)
                ->get();

            foreach ($assignments as $assignment) {
                $assignment->update([
                    'effective_to' => now()->toDateString(),
                    'is_active' => false,
                    'reason' => trim(($assignment->reason ? $assignment->reason.' · ' : '').'Ended on organization transfer'),
                ]);
                $this->auditLogger->log($assignment, 'wfh_assignment_ended', [
                    'reason' => 'organization_transfer',
                    'previous_organization_id' => $previousOrganizationId,
                    'new_organization_id' => $employee->organization_id,
                ], $actor);
                $ended++;
            }

            $requests = WfhRequest::withoutGlobalScopes()
                ->where('organization_id', $previousOrganizationId)
                ->where('employee_id', $employee->id)
                ->whereIn('status', ['draft', 'pending', 'approved'])
                ->get();

            foreach ($requests as $request) {
                if ($request->status === 'pending') {
                    $request->approvalSteps()
                        ->where('status', 'pending')
                        ->update(['status' => 'skipped', 'acted_at' => now()]);
                }

                $request->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);
                $this->auditLogger->log($request, 'wfh_request_cancelled', [
                    'reason' => 'organization_transfer',
                    'previous_organization_id' => $previousOrganizationId,
                    'new_organization_id' => $employee->organization_id,
                ], $actor);
                $cancelled++;
            }

            return [
                'ended_assignments' => $ended,
                'cancelled_requests' => $cancelled,
            ];
        });
    }

    protected function findApprovedDailyRequest(Employee $employee, Carbon $day): ?WfhRequest
    {
        return WfhRequest::query()
            ->where('organization_id', $employee->organization_id)
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($query) use ($day): void {
                $query->where(function ($range) use ($day): void {
                    $range->whereNotNull('start_date')
                        ->whereDate('start_date', '<=', $day->toDateString())
                        ->whereDate('end_date', '>=', $day->toDateString());
                })->orWhere(function ($legacy) use ($day): void {
                    $legacy->whereNull('start_date')
                        ->whereDate('work_date', $day->toDateString());
                });
            })
            ->first();
    }

    protected function findActiveAssignment(Employee $employee, Carbon $day, string $policyType): ?EmployeeWfhAssignment
    {
        return EmployeeWfhAssignment::query()
            ->where('organization_id', $employee->organization_id)
            ->where('employee_id', $employee->id)
            ->where('policy_type', $policyType)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $day->toDateString())
            ->where(function ($query) use ($day): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $day->toDateString());
            })
            ->orderByDesc('effective_from')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $orgPolicy
     * @return array{
     *     is_wfh: bool,
     *     policy_type: string,
     *     source: string,
     *     source_id: int,
     *     bypass_geofence: bool,
     *     record_gps: bool,
     *     organization_policy: array<string, mixed>
     * }
     */
    protected function activeResult(string $policyType, string $source, int $sourceId, array $orgPolicy): array
    {
        return [
            'is_wfh' => true,
            'policy_type' => $policyType,
            'source' => $source,
            'source_id' => $sourceId,
            'bypass_geofence' => (bool) $orgPolicy['bypass_geofence'],
            'record_gps' => (bool) $orgPolicy['record_gps_when_wfh'],
            'organization_policy' => $orgPolicy,
            'suppressed_by_leave' => false,
        ];
    }

    /**
     * @param  array<int, int>  $weekdays
     * @param  array<int, int>  $allowed
     */
    protected function assertValidWeekdays(array $weekdays, array $allowed): void
    {
        if ($weekdays === []) {
            throw ValidationException::withMessages([
                'weekdays' => __('Select at least one weekday for recurring WFH.'),
            ]);
        }

        foreach ($weekdays as $day) {
            if ($day < 1 || $day > 7) {
                throw ValidationException::withMessages([
                    'weekdays' => __('Weekdays must be ISO values between 1 (Monday) and 7 (Sunday).'),
                ]);
            }

            if ($allowed !== [] && ! in_array($day, $allowed, true)) {
                throw ValidationException::withMessages([
                    'weekdays' => __('One or more selected weekdays are not allowed by organization WFH policy.'),
                ]);
            }
        }
    }

    protected function assertNoOverlappingAssignment(
        Employee $employee,
        Carbon $from,
        ?Carbon $to,
        string $policyType,
        ?int $ignoreId = null,
    ): void {
        $query = EmployeeWfhAssignment::query()
            ->where('organization_id', $employee->organization_id)
            ->where('employee_id', $employee->id)
            ->where('policy_type', $policyType)
            ->where('is_active', true)
            ->where(function ($q) use ($from, $to): void {
                $q->where(function ($inner) use ($from, $to): void {
                    $inner->whereDate('effective_from', '<=', $to?->toDateString() ?? '9999-12-31')
                        ->where(function ($effectiveTo) use ($from): void {
                            $effectiveTo->whereNull('effective_to')
                                ->orWhereDate('effective_to', '>=', $from->toDateString());
                        });
                });
            });

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'effective_from' => __('An overlapping active WFH assignment already exists for this employee.'),
            ]);
        }
    }
}
