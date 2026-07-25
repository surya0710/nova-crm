<?php

namespace App\Services\Hrms;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\HrmsAnnouncement;
use App\Models\LeaveApplication;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OrganizationCalendarService
{
    /** @return Collection<int, array<string, mixed>> */
    public function eventsForMonth(int $year, int $month): Collection
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return collect()
            ->merge($this->holidays($start, $end))
            ->merge($this->birthdays($start, $end))
            ->merge($this->workAnniversaries($start, $end))
            ->merge($this->approvedLeave($start, $end))
            ->merge($this->companyEvents($start, $end))
            ->sortBy('date')
            ->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    protected function holidays(Carbon $start, Carbon $end): Collection
    {
        return Holiday::query()
            ->whereBetween('holiday_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('holiday_date')
            ->get()
            ->map(fn (Holiday $holiday) => [
                'type' => 'holiday',
                'date' => $holiday->holiday_date->toDateString(),
                'title' => $holiday->name,
                'description' => $holiday->is_optional ? 'Optional holiday' : 'Public holiday',
                'metadata' => ['is_optional' => $holiday->is_optional],
            ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    protected function birthdays(Carbon $start, Carbon $end): Collection
    {
        return Employee::query()
            ->whereNotNull('date_of_birth')
            ->whereIn('status', config('hrms.leave_applicable_employee_statuses', []))
            ->get()
            ->filter(function (Employee $employee) use ($start): bool {
                if (! $employee->date_of_birth) {
                    return false;
                }

                $birthday = $employee->date_of_birth->copy()->year($start->year);

                return $birthday->between($start, $start->copy()->endOfMonth());
            })
            ->map(fn (Employee $employee) => [
                'type' => 'birthday',
                'date' => $employee->date_of_birth->copy()->year($start->year)->toDateString(),
                'title' => $employee->full_name.' — Birthday',
                'description' => 'Employee birthday',
                'metadata' => ['employee_id' => $employee->id],
            ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    protected function workAnniversaries(Carbon $start, Carbon $end): Collection
    {
        return Employee::query()
            ->whereNotNull('joining_date')
            ->whereIn('status', config('hrms.leave_applicable_employee_statuses', []))
            ->get()
            ->filter(function (Employee $employee) use ($start): bool {
                if (! $employee->joining_date || $employee->joining_date->year >= $start->year) {
                    return false;
                }

                $anniversary = $employee->joining_date->copy()->year($start->year);

                return $anniversary->between($start, $start->copy()->endOfMonth());
            })
            ->map(function (Employee $employee) use ($start) {
                $years = $start->year - $employee->joining_date->year;

                return [
                    'type' => 'work_anniversary',
                    'date' => $employee->joining_date->copy()->year($start->year)->toDateString(),
                    'title' => $employee->full_name." — {$years} Year Anniversary",
                    'description' => "{$years} years of service",
                    'metadata' => ['employee_id' => $employee->id, 'years' => $years],
                ];
            });
    }

    /** @return Collection<int, array<string, mixed>> */
    protected function approvedLeave(Carbon $start, Carbon $end): Collection
    {
        return LeaveApplication::query()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->with(['employee', 'leaveType'])
            ->get()
            ->flatMap(function (LeaveApplication $leave) use ($start, $end) {
                $events = collect();
                $cursor = $leave->start_date->copy()->max($start);
                $leaveEnd = $leave->end_date->copy()->min($end);

                while ($cursor->lte($leaveEnd)) {
                    $events->push([
                        'type' => 'leave',
                        'date' => $cursor->toDateString(),
                        'title' => $leave->employee->full_name.' — '.($leave->leaveType->name ?? 'Leave'),
                        'description' => 'Approved leave',
                        'metadata' => [
                            'employee_id' => $leave->employee_id,
                            'leave_type' => $leave->leaveType->name ?? null,
                        ],
                    ]);
                    $cursor->addDay();
                }

                return $events;
            });
    }

    /** @return Collection<int, array<string, mixed>> */
    protected function companyEvents(Carbon $start, Carbon $end): Collection
    {
        return HrmsAnnouncement::query()
            ->active()
            ->where(function ($query) use ($start, $end): void {
                $query->where(function ($inner) use ($start, $end): void {
                    $inner->whereDate('start_date', '<=', $end)
                        ->where(function ($q) use ($start): void {
                            $q->whereNull('end_date')->orWhereDate('end_date', '>=', $start);
                        });
                });
            })
            ->get()
            ->map(fn (HrmsAnnouncement $announcement) => [
                'type' => 'company_event',
                'date' => ($announcement->start_date ?? $announcement->published_at ?? $announcement->created_at)->toDateString(),
                'title' => $announcement->title,
                'description' => str($announcement->body)->limit(100)->toString(),
                'metadata' => ['announcement_id' => $announcement->id],
            ]);
    }
}
