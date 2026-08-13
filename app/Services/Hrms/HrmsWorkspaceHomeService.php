<?php

namespace App\Services\Hrms;

use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Models\Holiday;
use App\Models\InterviewRound;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\LeaveApplication;
use App\Models\PayrollRun;
use App\Models\PerformanceReview;
use App\Models\User;
use App\Models\UserUiPreference;
use App\Services\Navigation\ShellQuickActionService;
use App\Services\TenantContext;
use App\Services\Workspace\CachesWorkspaceHome;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class HrmsWorkspaceHomeService
{
    use CachesWorkspaceHome;

    public function __construct(
        protected TenantContext $tenant,
        protected HrmsDashboardService $dashboard,
        protected ShellQuickActionService $shellQuickActions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        return $this->rememberHome('hr', $user, fn () => $this->buildUncached($user));
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildUncached(User $user): array
    {
        $organization = $this->tenant->get();
        $prefs = UserUiPreference::query()
            ->where('user_id', $user->id)
            ->where('organization_id', $organization?->id)
            ->first();

        $hrData = $user->hasPermission('hr.dashboard') || $user->hasPermission('hrms.view')
            ? $this->dashboard->hrDashboard()
            : null;

        return [
            'kpis' => $this->kpis($user, $hrData),
            'attention' => $this->attention($user, $hrData),
            'employeeSummary' => $this->employeeSummary($user, $hrData),
            'attendanceToday' => $this->attendanceToday($user, $hrData),
            'attendancePercentage' => $this->attendancePercentage($user, $hrData),
            'employeesOnLeave' => $this->employeesOnLeave($user, $hrData),
            'pendingLeaveRequests' => $this->pendingLeaveRequests($user, $hrData),
            'upcomingBirthdays' => $this->upcomingBirthdays($user, $hrData),
            'upcomingAnniversaries' => $this->upcomingAnniversaries($user, $hrData),
            'newJoiners' => $this->newJoiners($user),
            'recruitmentPipeline' => $this->recruitmentPipeline($user),
            'openPositions' => $this->openPositions($user),
            'interviewSchedule' => $this->interviewSchedule($user),
            'performanceOverview' => $this->performanceOverview($user),
            'payrollSummary' => $this->payrollSummary($user),
            'departmentOverview' => $this->departmentOverview($user, $hrData),
            'assetsAssigned' => $this->assetsAssigned($user, $hrData),
            'upcomingHolidays' => $this->upcomingHolidays($user),
            'recentActivities' => $this->recentActivities($user),
            'quickActions' => $this->quickActions($user, $organization),
            'pinnedPages' => $this->pinnedHrPages($prefs),
            'widgetLayout' => $prefs?->dashboard_layout['hr'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $hrData
     * @return array<int, array{label: string, value: string|int, hint?: string|null}>
     */
    protected function kpis(User $user, ?array $hrData): array
    {
        $kpis = [];

        if ($hrData) {
            $kpis[] = [
                'label' => __('Active employees'),
                'value' => $hrData['activeEmployees'] ?? 0,
                'hint' => __('Headcount'),
            ];
            $kpis[] = [
                'label' => __('Pending leave'),
                'value' => $hrData['leaveStats']['pending_approvals'] ?? ($hrData['pendingLeaveApprovals']?->count() ?? 0),
                'hint' => __('Awaiting approval'),
            ];
        }

        if ($user->hasPermission('recruitment.view') && Schema::hasTable('job_openings')) {
            $kpis[] = [
                'label' => __('Open openings'),
                'value' => JobOpening::query()->whereIn('status', ['published', 'paused'])->count(),
            ];
            $kpis[] = [
                'label' => __('In interview'),
                'value' => JobApplication::query()->whereIn('stage', ['interview', 'evaluation'])->count(),
            ];
        }

        if ($user->hasPermission('payroll.view') && Schema::hasTable('payroll_runs')) {
            $run = PayrollRun::query()->with('period')->latest('id')->first();
            $kpis[] = [
                'label' => __('Payroll status'),
                'value' => $run?->status ?? __('—'),
                'hint' => $run?->period?->name ?? __('Latest run'),
            ];
        }

        if ($user->hasPermission('attendance.view') && $hrData) {
            $summary = $hrData['attendanceSummary'] ?? [];
            $present = is_array($summary)
                ? ($summary['present'] ?? $summary['present_count'] ?? null)
                : ($summary->present ?? $summary->present_count ?? null);
            if ($present !== null) {
                $kpis[] = [
                    'label' => __('Present today'),
                    'value' => $present,
                ];
            }
        }

        return array_slice($kpis, 0, 6);
    }

    /**
     * @param  array<string, mixed>|null  $hrData
     * @return Collection<int, array{title: string, subtitle?: string|null, href?: string|null, badge?: string|null}>
     */
    protected function attention(User $user, ?array $hrData): Collection
    {
        $items = collect();

        if ($hrData) {
            foreach (($hrData['pendingLeaveApprovals'] ?? collect())->take(5) as $leave) {
                $items->push([
                    'title' => $leave->employee?->full_name ?? __('Leave request'),
                    'subtitle' => $leave->leaveType?->name,
                    'href' => Route::has('hrms.leave-applications.show')
                        ? route('hrms.leave-applications.show', $leave)
                        : route('hrms.leave.dashboard'),
                    'badge' => __('Leave'),
                ]);
            }

            foreach (($hrData['pendingCorrections'] ?? collect())->take(3) as $correction) {
                $items->push([
                    'title' => $correction->employee?->full_name ?? __('Attendance correction'),
                    'subtitle' => __('Attendance correction'),
                    'href' => Route::has('hrms.attendance.index')
                        ? route('hrms.attendance.index')
                        : null,
                    'badge' => __('attendance.label'),
                ]);
            }

            foreach (($hrData['expiringDocuments'] ?? collect())->take(3) as $doc) {
                $items->push([
                    'title' => $doc->employee?->full_name ?? __('Document'),
                    'subtitle' => __('Expires :date', ['date' => optional($doc->expires_at)->format('M j')]),
                    'href' => $doc->employee && Route::has('hrms.employees.documents.index')
                        ? route('hrms.employees.documents.index', $doc->employee)
                        : null,
                    'badge' => __('Document'),
                ]);
            }
        }

        return $items->take(8)->values();
    }

    /**
     * @param  array<string, mixed>|null  $hrData
     * @return array{active: int, total: int, new_joiners: int, href: string|null}|null
     */
    protected function employeeSummary(User $user, ?array $hrData): ?array
    {
        if (! $hrData) {
            return null;
        }

        return [
            'active' => (int) ($hrData['activeEmployees'] ?? 0),
            'total' => (int) ($hrData['employeeCount'] ?? 0),
            'new_joiners' => (int) ($hrData['newJoiners'] ?? 0),
            'href' => Route::has('hrms.employees.index') && $user->hasPermission('hrms.view')
                ? route('hrms.employees.index')
                : null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $hrData
     * @return array<string, mixed>|null
     */
    protected function attendanceToday(User $user, ?array $hrData): ?array
    {
        if (! $user->hasPermission('attendance.view') && ! $hrData) {
            return null;
        }

        $summary = $hrData['attendanceSummary'] ?? null;
        if (! $summary) {
            return [
                'href' => Route::has('hrms.attendance.index') ? route('hrms.attendance.index') : null,
                'rows' => [],
            ];
        }

        $asArray = is_array($summary) ? $summary : (array) $summary;

        return [
            'href' => Route::has('hrms.attendance.index') ? route('hrms.attendance.index') : null,
            'present' => $asArray['present'] ?? $asArray['present_count'] ?? 0,
            'absent' => $asArray['absent'] ?? $asArray['absent_count'] ?? 0,
            'late' => $asArray['late'] ?? $asArray['late_count'] ?? 0,
            'on_leave' => $asArray['on_leave'] ?? $asArray['leave_count'] ?? ($hrData['onLeaveToday'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $hrData
     * @return array{percent: int, present: int, expected: int, href: string|null}|null
     */
    protected function attendancePercentage(User $user, ?array $hrData): ?array
    {
        if (! $user->hasPermission('attendance.view') || ! $hrData) {
            return null;
        }

        $summary = $hrData['attendanceSummary'] ?? null;
        if (! $summary) {
            return null;
        }

        $asArray = is_array($summary) ? $summary : (array) $summary;
        $present = (int) ($asArray['present'] ?? $asArray['present_count'] ?? 0);
        $late = (int) ($asArray['late'] ?? $asArray['late_count'] ?? 0);
        $halfDay = (int) ($asArray['half_day'] ?? 0);
        $expected = (int) ($asArray['total_employees'] ?? 0);
        $attended = $present + $late + $halfDay;
        $percent = $expected > 0 ? (int) round(($attended / $expected) * 100) : 0;

        return [
            'percent' => $percent,
            'present' => $attended,
            'expected' => $expected,
            'href' => Route::has('hrms.attendance.index') ? route('hrms.attendance.index') : null,
        ];
    }

    /**
     * @return array{status: string, period: string|null, href: string|null}|null
     */
    protected function payrollSummary(User $user): ?array
    {
        if (! $user->hasPermission('payroll.view') || ! Schema::hasTable('payroll_runs')) {
            return null;
        }

        $run = PayrollRun::query()->with('period')->latest('id')->first();

        return [
            'status' => $run?->status ?? __('No runs'),
            'period' => $run?->period?->name ?? $run?->period?->label ?? null,
            'href' => Route::has('hrms.payroll.index') ? route('hrms.payroll.index') : null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $hrData
     * @return Collection<int, array{name: string, total: int}>
     */
    protected function departmentOverview(User $user, ?array $hrData): Collection
    {
        if (! $user->hasPermission('hrms.view') && ! $user->hasPermission('hr.dashboard')) {
            return collect();
        }

        if ($hrData && isset($hrData['departmentDistribution'])) {
            return collect($hrData['departmentDistribution'])
                ->map(fn ($row) => [
                    'name' => is_object($row) ? (string) ($row->department_name ?? __('Unassigned')) : (string) ($row['department_name'] ?? __('Unassigned')),
                    'total' => is_object($row) ? (int) ($row->total ?? 0) : (int) ($row['total'] ?? 0),
                ])
                ->take(8)
                ->values();
        }

        return Employee::query()
            ->with('department:id,name')
            ->whereNull('deleted_at')
            ->get(['id', 'department_id'])
            ->groupBy(fn (Employee $employee) => $employee->department?->name ?? __('Unassigned'))
            ->map(fn ($group, $name) => [
                'name' => (string) $name,
                'total' => $group->count(),
            ])
            ->sortByDesc('total')
            ->take(8)
            ->values();
    }

    /**
     * @param  array<string, mixed>|null  $hrData
     * @return Collection<int, LeaveApplication>
     */
    protected function employeesOnLeave(User $user, ?array $hrData): Collection
    {
        if (! $user->hasPermission('leave.view') && ! $user->hasPermission('hr.dashboard')) {
            return collect();
        }

        $today = Carbon::today()->toDateString();

        return LeaveApplication::query()
            ->with(['employee', 'leaveType'])
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->limit(8)
            ->get();
    }

    /**
     * @param  array<string, mixed>|null  $hrData
     * @return Collection<int, LeaveApplication>
     */
    protected function pendingLeaveRequests(User $user, ?array $hrData): Collection
    {
        if ($hrData && isset($hrData['pendingLeaveApprovals'])) {
            return collect($hrData['pendingLeaveApprovals']);
        }

        if (! $user->hasPermission('leave.view') && ! $user->hasPermission('leave.approve')) {
            return collect();
        }

        return LeaveApplication::query()
            ->with(['employee', 'leaveType'])
            ->where('status', 'pending')
            ->latest('submitted_at')
            ->limit(8)
            ->get();
    }

    /**
     * @param  array<string, mixed>|null  $hrData
     * @return Collection<int, Employee>
     */
    protected function upcomingBirthdays(User $user, ?array $hrData): Collection
    {
        if ($hrData && isset($hrData['upcomingBirthdays'])) {
            return collect($hrData['upcomingBirthdays']);
        }

        if (! $user->hasPermission('hrms.view') && ! $user->hasPermission('hr.dashboard')) {
            return collect();
        }

        return Employee::query()
            ->whereNotNull('date_of_birth')
            ->whereMonth('date_of_birth', now()->month)
            ->orderByRaw('DAY(date_of_birth)')
            ->limit(8)
            ->get();
    }

    /**
     * @param  array<string, mixed>|null  $hrData
     * @return Collection<int, Employee>
     */
    protected function upcomingAnniversaries(User $user, ?array $hrData): Collection
    {
        if ($hrData && isset($hrData['workAnniversaries'])) {
            return collect($hrData['workAnniversaries']);
        }

        if (! $user->hasPermission('hrms.view') && ! $user->hasPermission('hr.dashboard')) {
            return collect();
        }

        return Employee::query()
            ->whereNotNull('joining_date')
            ->whereMonth('joining_date', now()->month)
            ->whereYear('joining_date', '<', now()->year)
            ->orderByRaw('DAY(joining_date)')
            ->limit(8)
            ->get();
    }

    /**
     * @return Collection<int, Employee>
     */
    protected function newJoiners(User $user): Collection
    {
        if (! $user->hasPermission('hrms.view') && ! $user->hasPermission('hr.dashboard')) {
            return collect();
        }

        return Employee::query()
            ->with(['department', 'designation'])
            ->whereMonth('joining_date', now()->month)
            ->whereYear('joining_date', now()->year)
            ->latest('joining_date')
            ->limit(8)
            ->get();
    }

    /**
     * @return array{stages: array<string, int>, href: string|null}|null
     */
    protected function recruitmentPipeline(User $user): ?array
    {
        if (! $user->hasPermission('recruitment.view') || ! Schema::hasTable('job_applications')) {
            return null;
        }

        $stages = JobApplication::query()
            ->selectRaw('stage, count(*) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage')
            ->all();

        return [
            'stages' => $stages,
            'href' => Route::has('hrms.recruitment.dashboard')
                ? route('hrms.recruitment.dashboard')
                : null,
        ];
    }

    /**
     * @return Collection<int, JobOpening>
     */
    protected function openPositions(User $user): Collection
    {
        if (! $user->hasPermission('recruitment.view') || ! Schema::hasTable('job_openings')) {
            return collect();
        }

        return JobOpening::query()
            ->with('department')
            ->whereIn('status', ['published', 'paused'])
            ->latest()
            ->limit(6)
            ->get();
    }

    /**
     * @return Collection<int, InterviewRound>
     */
    protected function interviewSchedule(User $user): Collection
    {
        if (! $user->hasPermission('recruitment.view') || ! Schema::hasTable('interview_rounds')) {
            return collect();
        }

        return InterviewRound::query()
            ->with(['jobApplication.candidate', 'jobApplication.jobOpening'])
            ->where('scheduled_at', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at')
            ->limit(6)
            ->get();
    }

    /**
     * @return array{open_reviews: int, href: string|null}|null
     */
    protected function performanceOverview(User $user): ?array
    {
        if (! $user->hasAnyPermission(['performance.view', 'performance.review.view', 'performance.goal.view'])) {
            return null;
        }

        if (! Schema::hasTable('performance_reviews')) {
            return [
                'open_reviews' => 0,
                'href' => Route::has('hrms.performance.index') ? route('hrms.performance.index') : null,
            ];
        }

        return [
            'open_reviews' => PerformanceReview::query()
                ->whereNotIn('status', ['completed', 'cancelled', 'closed'])
                ->count(),
            'href' => Route::has('hrms.performance.index') ? route('hrms.performance.index') : null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $hrData
     * @return array{assigned: int, href: string|null}|null
     */
    protected function assetsAssigned(User $user, ?array $hrData): ?array
    {
        if (! $user->hasPermission('assets.view') && ! ($hrData['assetStats'] ?? null)) {
            return null;
        }

        $assigned = $hrData['assetStats']['assigned']
            ?? $hrData['assetStats']['assigned_count']
            ?? (Schema::hasTable('employee_assets')
                ? EmployeeAsset::query()->whereIn('status', ['assigned', 'in_use'])->count()
                : 0);

        return [
            'assigned' => (int) $assigned,
            'href' => Route::has('hrms.assets.index') ? route('hrms.assets.index') : null,
        ];
    }

    /**
     * @return Collection<int, Holiday>
     */
    protected function upcomingHolidays(User $user): Collection
    {
        if (! Schema::hasTable('holidays')) {
            return collect();
        }

        if (! $user->hasAnyPermission(['hrms.view', 'organization.calendar', 'leave.view', 'ess.access'])) {
            return collect();
        }

        return Holiday::query()
            ->whereDate('holiday_date', '>=', now()->toDateString())
            ->orderBy('holiday_date')
            ->limit(6)
            ->get();
    }

    /**
     * @return Collection<int, array{title: string, subtitle: string, href: string|null, when: string}>
     */
    protected function recentActivities(User $user): Collection
    {
        $items = collect();

        if ($user->hasPermission('hrms.view') || $user->hasPermission('hr.dashboard')) {
            Employee::query()
                ->latest('updated_at')
                ->limit(4)
                ->get()
                ->each(function (Employee $employee) use ($items) {
                    $items->push([
                        'title' => $employee->full_name,
                        'subtitle' => __('Employee updated'),
                        'href' => Route::has('hrms.employees.show')
                            ? route('hrms.employees.show', $employee)
                            : null,
                        'when' => $employee->updated_at?->diffForHumans() ?? '',
                    ]);
                });
        }

        if ($user->hasPermission('leave.view')) {
            LeaveApplication::query()
                ->with('employee')
                ->latest('updated_at')
                ->limit(4)
                ->get()
                ->each(function (LeaveApplication $leave) use ($items) {
                    $items->push([
                        'title' => $leave->employee?->full_name ?? __('Leave'),
                        'subtitle' => __('Leave :status', ['status' => $leave->status]),
                        'href' => Route::has('hrms.leave-applications.show')
                            ? route('hrms.leave-applications.show', $leave)
                            : null,
                        'when' => $leave->updated_at?->diffForHumans() ?? '',
                    ]);
                });
        }

        return $items->sortByDesc('when')->take(8)->values();
    }

    /**
     * @return array{primary: array<int, array{label: string, href: string, variant?: string}>, overflow: array<int, array{label: string, href: string, variant?: string}>, all: array<int, array{label: string, href: string, variant?: string}>}
     */
    protected function quickActions(User $user, $organization): array
    {
        if (! $organization) {
            return ['primary' => [], 'overflow' => [], 'all' => []];
        }

        return $this->shellQuickActions->forWorkspace($user, $organization, 'hr');
    }

    /**
     * @return Collection<int, array{label: string, href: string}>
     */
    protected function pinnedHrPages(?UserUiPreference $prefs): Collection
    {
        $favorites = collect($prefs?->favorites ?? []);

        return $favorites
            ->filter(fn ($item) => is_array($item) && str_contains((string) ($item['href'] ?? $item['url'] ?? ''), '/hrms'))
            ->map(fn ($item) => [
                'label' => $item['label'] ?? $item['title'] ?? __('Pinned'),
                'href' => $item['href'] ?? $item['url'],
            ])
            ->take(5)
            ->values();
    }
}
