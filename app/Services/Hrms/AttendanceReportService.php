<?php

namespace App\Services\Hrms;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveApplication;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AttendanceReportService
{
    public function __construct(
        protected AttendanceCalendarService $calendar,
    ) {}

    /**
     * @return list<array{type: string, label: string}>
     */
    public function availableReports(): array
    {
        return collect(config('hrms.attendance_reports.types', []))
            ->map(fn (string $label, string $type) => ['type' => $type, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * @param  array{year?: int, month?: int, department_id?: int|null, employee_id?: int|null}  $filters
     * @return array<string, mixed>
     */
    public function compile(string $reportType, array $filters = []): array
    {
        $types = config('hrms.attendance_reports.types', []);
        if (! array_key_exists($reportType, $types)) {
            throw ValidationException::withMessages([
                'report_type' => __('Invalid report type.'),
            ]);
        }

        $year = (int) ($filters['year'] ?? now()->year);
        $month = (int) ($filters['month'] ?? now()->month);
        [$year, $month] = array_values($this->calendar->normalizeYearMonth($year, $month));
        $departmentId = isset($filters['department_id']) ? (int) $filters['department_id'] : null;
        $employeeId = isset($filters['employee_id']) ? (int) $filters['employee_id'] : null;

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $employees = $this->resolveEmployees($departmentId, $employeeId);

        $rows = match ($reportType) {
            'monthly_attendance' => $this->monthlyAttendanceRows($employees, $year, $month),
            'late_report' => $this->lateRows($employees, $start, $end),
            'absent_report' => $this->absentRows($employees, $year, $month),
            'leave_summary' => $this->leaveSummaryRows($employees, $start, $end),
            default => [],
        };

        return [
            'report_type' => $reportType,
            'report_label' => $types[$reportType],
            'filters' => [
                'year' => $year,
                'month' => $month,
                'month_label' => $start->format('F Y'),
                'department_id' => $departmentId,
                'employee_id' => $employeeId,
            ],
            'generated_at' => now()->toIso8601String(),
            'columns' => $this->columnsFor($reportType),
            'rows' => $rows,
            'totals' => $this->totalsFor($reportType, $rows),
        ];
    }

    /**
     * @return Collection<int, Employee>
     */
    protected function resolveEmployees(?int $departmentId, ?int $employeeId): Collection
    {
        $query = Employee::query()
            ->whereIn('status', config('hrms.clockable_employee_statuses', []))
            ->with(['department:id,name'])
            ->orderBy('first_name')
            ->orderBy('last_name');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($employeeId) {
            $query->where('id', $employeeId);
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return list<array<string, mixed>>
     */
    protected function monthlyAttendanceRows(Collection $employees, int $year, int $month): array
    {
        return $employees->map(function (Employee $employee) use ($year, $month) {
            $calendar = $this->calendar->monthForEmployee($employee, $year, $month);
            $summary = $calendar['summary'];

            return [
                'employee_code' => $employee->employee_code,
                'employee_name' => $employee->full_name,
                'department' => $employee->department?->name,
                'present' => $summary['present'] ?? 0,
                'late' => $summary['late'] ?? 0,
                'half_day' => $summary['half_day'] ?? 0,
                'absent' => $summary['absent'] ?? 0,
                'leave' => $summary['leave_approved'] ?? 0,
                'holiday' => $summary['holiday'] ?? 0,
                'weekend' => $summary['weekend'] ?? 0,
                'wfh' => $summary['remote'] ?? 0,
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return list<array<string, mixed>>
     */
    protected function lateRows(Collection $employees, Carbon $start, Carbon $end): array
    {
        if ($employees->isEmpty()) {
            return [];
        }

        return AttendanceRecord::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($query) {
                $query->where('status', 'late')
                    ->orWhere('late_minutes', '>', 0);
            })
            ->with(['employee.department:id,name', 'shift:id,name'])
            ->orderBy('attendance_date')
            ->get()
            ->map(fn (AttendanceRecord $record) => [
                'date' => $record->attendance_date->toDateString(),
                'employee_code' => $record->employee?->employee_code,
                'employee_name' => $record->employee?->full_name,
                'department' => $record->employee?->department?->name,
                'check_in' => $record->clock_in_at?->format('g:i A'),
                'late_minutes' => (int) $record->late_minutes,
                'shift' => $record->shift?->name,
                'status' => $record->statusLabel(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return list<array<string, mixed>>
     */
    protected function absentRows(Collection $employees, int $year, int $month): array
    {
        $rows = [];

        foreach ($employees as $employee) {
            $calendar = $this->calendar->monthForEmployee($employee, $year, $month);
            foreach ($calendar['days'] as $day) {
                if (($day['visual']['key'] ?? null) !== 'absent') {
                    continue;
                }

                $rows[] = [
                    'date' => $day['date'],
                    'employee_code' => $employee->employee_code,
                    'employee_name' => $employee->full_name,
                    'department' => $employee->department?->name,
                    'status' => __('Absent'),
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return list<array<string, mixed>>
     */
    protected function leaveSummaryRows(Collection $employees, Carbon $start, Carbon $end): array
    {
        if ($employees->isEmpty()) {
            return [];
        }

        return LeaveApplication::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereIn('status', ['approved', 'pending'])
            ->where(function ($query) use ($start, $end): void {
                $query->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere(function ($inner) use ($start, $end): void {
                        $inner->where('start_date', '<=', $start->toDateString())
                            ->where('end_date', '>=', $end->toDateString());
                    });
            })
            ->with(['employee.department:id,name', 'leaveType:id,name,code'])
            ->orderBy('start_date')
            ->get()
            ->map(fn (LeaveApplication $application) => [
                'employee_code' => $application->employee?->employee_code,
                'employee_name' => $application->employee?->full_name,
                'department' => $application->employee?->department?->name,
                'leave_type' => $application->leaveType?->name,
                'status' => config('hrms.leave_statuses.'.$application->status, ucfirst($application->status)),
                'start_date' => $application->start_date->toDateString(),
                'end_date' => $application->end_date->toDateString(),
                'days' => (float) $application->days,
                'is_half_day' => $application->is_half_day ? __('Yes') : __('No'),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    protected function columnsFor(string $reportType): array
    {
        return match ($reportType) {
            'monthly_attendance' => [
                ['key' => 'employee_code', 'label' => __('Code')],
                ['key' => 'employee_name', 'label' => __('Employee')],
                ['key' => 'department', 'label' => __('Department')],
                ['key' => 'present', 'label' => __('Present')],
                ['key' => 'late', 'label' => __('Late')],
                ['key' => 'half_day', 'label' => __('Half Day')],
                ['key' => 'absent', 'label' => __('Absent')],
                ['key' => 'leave', 'label' => __('Leave')],
                ['key' => 'holiday', 'label' => __('Holiday')],
                ['key' => 'weekend', 'label' => __('Weekend')],
                ['key' => 'wfh', 'label' => __('WFH')],
            ],
            'late_report' => [
                ['key' => 'date', 'label' => __('Date')],
                ['key' => 'employee_code', 'label' => __('Code')],
                ['key' => 'employee_name', 'label' => __('Employee')],
                ['key' => 'department', 'label' => __('Department')],
                ['key' => 'check_in', 'label' => __('Check In')],
                ['key' => 'late_minutes', 'label' => __('Late Minutes')],
                ['key' => 'shift', 'label' => __('Shift')],
                ['key' => 'status', 'label' => __('Status')],
            ],
            'absent_report' => [
                ['key' => 'date', 'label' => __('Date')],
                ['key' => 'employee_code', 'label' => __('Code')],
                ['key' => 'employee_name', 'label' => __('Employee')],
                ['key' => 'department', 'label' => __('Department')],
                ['key' => 'status', 'label' => __('Status')],
            ],
            'leave_summary' => [
                ['key' => 'employee_code', 'label' => __('Code')],
                ['key' => 'employee_name', 'label' => __('Employee')],
                ['key' => 'department', 'label' => __('Department')],
                ['key' => 'leave_type', 'label' => __('Leave Type')],
                ['key' => 'status', 'label' => __('Status')],
                ['key' => 'start_date', 'label' => __('Start')],
                ['key' => 'end_date', 'label' => __('End')],
                ['key' => 'days', 'label' => __('Days')],
                ['key' => 'is_half_day', 'label' => __('Half Day')],
            ],
            default => [],
        };
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, int|float>
     */
    protected function totalsFor(string $reportType, array $rows): array
    {
        return match ($reportType) {
            'monthly_attendance' => [
                'employees' => count($rows),
                'present' => (int) collect($rows)->sum('present'),
                'late' => (int) collect($rows)->sum('late'),
                'absent' => (int) collect($rows)->sum('absent'),
                'leave' => (int) collect($rows)->sum('leave'),
            ],
            'late_report' => [
                'entries' => count($rows),
                'late_minutes' => (int) collect($rows)->sum('late_minutes'),
            ],
            'absent_report' => [
                'entries' => count($rows),
            ],
            'leave_summary' => [
                'entries' => count($rows),
                'days' => (float) collect($rows)->sum('days'),
            ],
            default => ['entries' => count($rows)],
        };
    }

    /**
     * @return Collection<int, Department>
     */
    public function departments(): Collection
    {
        return Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
