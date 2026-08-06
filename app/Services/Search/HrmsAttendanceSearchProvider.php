<?php

namespace App\Services\Search;

use App\Models\AttendanceRecord;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;

class HrmsAttendanceSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'attendance';
    }

    public function label(): string
    {
        return __('Attendance');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasPermission('attendance.view')) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        return AttendanceRecord::query()
            ->with('employee')
            ->where(function ($q) use ($query) {
                $q->where('status', 'like', "%{$query}%")
                    ->orWhere('attendance_date', 'like', "%{$query}%")
                    ->orWhereHas('employee', function ($employee) use ($query) {
                        $employee->where('first_name', 'like', "%{$query}%")
                            ->orWhere('last_name', 'like', "%{$query}%")
                            ->orWhere('employee_code', 'like', "%{$query}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$query}%"]);
                    });
            })
            ->latest('attendance_date')
            ->limit($limit)
            ->get()
            ->map(fn (AttendanceRecord $record) => [
                'type' => __('Attendance'),
                'label' => $this->label(),
                'title' => $record->employee?->full_name ?? __('Attendance'),
                'subtitle' => trim(($record->attendance_date?->format('Y-m-d') ?? '').' · '.($record->status ?? '')),
                'url' => route('hrms.attendance.index', ['search' => $record->employee?->employee_code]),
                'workspace' => 'hr',
            ]);
    }
}
