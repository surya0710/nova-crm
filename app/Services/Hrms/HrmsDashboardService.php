<?php

namespace App\Services\Hrms;

use App\Events\AnnouncementCreated;
use App\Events\AnnouncementDeleted;
use App\Events\AnnouncementUpdated;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\HrmsAnnouncement;
use App\Models\LeaveApplication;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class HrmsDashboardService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AttendanceService $attendanceService,
        protected LeaveService $leaveService,
        protected AssetService $assetService,
        protected EmployeeExitService $exitService,
        protected AuditLogger $auditLogger,
    ) {}

    /** @return array<string, mixed> */
    public function employeeDashboard(Employee $employee): array
    {
        $employee->load(['department', 'designation', 'reportingManager', 'branch']);
        $today = now()->startOfDay();

        $todayRecord = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->with('shift')
            ->first();

        return [
            'employee' => $employee,
            'todayAttendance' => $todayRecord,
            'currentShift' => $this->attendanceService->resolveShiftForEmployee($employee, $today),
            'onLeaveToday' => $this->leaveService->getApprovedLeaveForDate($employee, $today)->isNotEmpty(),
            'leaveBalances' => $this->leaveService->getBalancesForEmployee($employee),
            'pendingLeave' => LeaveApplication::query()
                ->where('employee_id', $employee->id)
                ->where('status', 'pending')
                ->with('leaveType')
                ->latest('submitted_at')
                ->limit(5)
                ->get(),
            'recentDocuments' => $employee->documents()
                ->with('currentVersion')
                ->latest()
                ->limit(5)
                ->get(),
            'announcements' => $this->announcementsForUser($employee->user, 5),
        ];
    }

    /** @return array<string, mixed> */
    public function managerDashboard(Employee $manager): array
    {
        $teamIds = Employee::query()
            ->where('reporting_manager_id', $manager->id)
            ->pluck('id');

        $today = now()->toDateString();

        $onLeaveToday = LeaveApplication::query()
            ->whereIn('employee_id', $teamIds)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->with(['employee', 'leaveType'])
            ->get();

        $pendingLeave = LeaveApplication::query()
            ->whereIn('employee_id', $teamIds)
            ->where('status', 'pending')
            ->with(['employee', 'leaveType'])
            ->latest('submitted_at')
            ->limit(10)
            ->get();

        $pendingCorrections = AttendanceCorrection::query()
            ->whereIn('employee_id', $teamIds)
            ->where('status', 'pending')
            ->with(['employee', 'attendanceRecord'])
            ->latest()
            ->limit(10)
            ->get();

        $teamPresentToday = AttendanceRecord::query()
            ->whereIn('employee_id', $teamIds)
            ->whereDate('attendance_date', $today)
            ->whereIn('status', ['present', 'late', 'half_day'])
            ->count();

        $birthdays = Employee::query()
            ->whereIn('id', $teamIds)
            ->whereNotNull('date_of_birth')
            ->whereMonth('date_of_birth', now()->month)
            ->orderByRaw('DAY(date_of_birth)')
            ->limit(10)
            ->get();

        return [
            'manager' => $manager,
            'teamCount' => $teamIds->count(),
            'teamPresentToday' => $teamPresentToday,
            'onLeaveToday' => $onLeaveToday,
            'pendingLeave' => $pendingLeave,
            'pendingCorrections' => $pendingCorrections,
            'birthdays' => $birthdays,
            'announcements' => $this->announcementsForUser($manager->user, 5),
        ];
    }

    /** @return array<string, mixed> */
    public function hrDashboard(): array
    {
        $today = Carbon::today();
        $expiringDays = (int) config('hrms.document_expiring_soon_days', 30);

        return [
            'employeeCount' => Employee::query()->count(),
            'activeEmployees' => Employee::query()->whereIn('status', config('hrms.leave_applicable_employee_statuses', []))->count(),
            'newJoiners' => Employee::query()
                ->whereMonth('joining_date', $today->month)
                ->whereYear('joining_date', $today->year)
                ->count(),
            'onLeaveToday' => LeaveApplication::query()
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->count(),
            'attendanceSummary' => $this->attendanceService->dailySummary($today),
            'leaveStats' => $this->leaveService->dashboardStats(),
            'pendingLeaveApprovals' => LeaveApplication::query()
                ->where('status', 'pending')
                ->with(['employee', 'leaveType'])
                ->latest('submitted_at')
                ->limit(10)
                ->get(),
            'pendingCorrections' => AttendanceCorrection::query()
                ->where('status', 'pending')
                ->with(['employee', 'attendanceRecord'])
                ->latest()
                ->limit(10)
                ->get(),
            'expiringDocuments' => EmployeeDocument::query()
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '<=', $today->copy()->addDays($expiringDays))
                ->whereDate('expires_at', '>=', $today)
                ->with('employee')
                ->orderBy('expires_at')
                ->limit(10)
                ->get(),
            'announcements' => HrmsAnnouncement::query()->active()->latest()->limit(5)->get(),
            'assetStats' => $this->assetService->dashboardStats(),
            'exitStats' => $this->exitService->dashboardStats(),
            'upcomingBirthdays' => Employee::query()
                ->whereNotNull('date_of_birth')
                ->whereIn('status', config('hrms.leave_applicable_employee_statuses', []))
                ->whereMonth('date_of_birth', $today->month)
                ->orderByRaw('DAY(date_of_birth)')
                ->limit(10)
                ->get(),
            'workAnniversaries' => Employee::query()
                ->whereNotNull('joining_date')
                ->whereIn('status', config('hrms.leave_applicable_employee_statuses', []))
                ->whereMonth('joining_date', $today->month)
                ->whereYear('joining_date', '<', $today->year)
                ->orderByRaw('DAY(joining_date)')
                ->limit(10)
                ->get(),
            'departmentDistribution' => Employee::query()
                ->with('department:id,name')
                ->whereNull('deleted_at')
                ->get(['id', 'department_id'])
                ->groupBy(fn (Employee $employee) => $employee->department?->name ?? __('Unassigned'))
                ->map(fn ($group, $name) => (object) [
                    'department_name' => $name,
                    'total' => $group->count(),
                ])
                ->sortByDesc('total')
                ->values(),
        ];
    }

    /** @return Collection<int, HrmsAnnouncement> */
    public function announcementsForUser(?User $user, int $limit = 10): Collection
    {
        if ($user === null) {
            return new Collection;
        }

        $audiences = ['everyone'];

        if ($user->hasPermission('ess.access')) {
            $audiences[] = 'employees';
        }
        if ($user->hasPermission('manager.dashboard')) {
            $audiences[] = 'managers';
        }
        if ($user->hasPermission('hr.dashboard') || $user->hasPermission('hrms.view')) {
            $audiences[] = 'hr';
        }

        return HrmsAnnouncement::query()
            ->active()
            ->whereIn('target_audience', array_unique($audiences))
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function createAnnouncement(array $data, User $actor): HrmsAnnouncement
    {
        return DB::transaction(function () use ($data, $actor): HrmsAnnouncement {
            $announcement = HrmsAnnouncement::query()->create([
                ...$data,
                'created_by' => $actor->id,
            ]);

            $this->auditLogger->log($announcement, 'announcement_created', ['title' => $announcement->title], $actor);
            event(AnnouncementCreated::forModel($announcement, ['actor_id' => $actor->id]));

            return $announcement;
        });
    }

    public function updateAnnouncement(HrmsAnnouncement $announcement, array $data, User $actor): HrmsAnnouncement
    {
        return DB::transaction(function () use ($announcement, $data, $actor): HrmsAnnouncement {
            $before = $announcement->only(['title', 'body', 'target_audience', 'start_date', 'end_date', 'is_active']);
            $announcement->update($data);
            $this->auditLogger->log($announcement, 'announcement_updated', [
                'before' => $before,
                'after' => $announcement->only(array_keys($before)),
            ], $actor);
            event(AnnouncementUpdated::forModel($announcement, ['actor_id' => $actor->id]));

            return $announcement;
        });
    }

    public function deleteAnnouncement(HrmsAnnouncement $announcement, User $actor): void
    {
        DB::transaction(function () use ($announcement, $actor): void {
            $this->auditLogger->log($announcement, 'announcement_deleted', ['title' => $announcement->title], $actor);
            event(AnnouncementDeleted::forModel($announcement, ['actor_id' => $actor->id]));
            $announcement->delete();
        });
    }
}
