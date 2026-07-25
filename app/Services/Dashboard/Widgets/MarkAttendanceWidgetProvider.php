<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class MarkAttendanceWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'mark_attendance';
    }

    public function subscriptionModule(): ?string
    {
        return 'hrms';
    }

    public function permissionSlug(): ?string
    {
        return 'ess.access';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        if (! Schema::hasTable('attendance_records')) {
            return ['clocked_in' => false, 'available' => false];
        }

        $employee = \App\Models\Employee::query()
            ->where('user_id', $user->id)
            ->first();

        if (! $employee) {
            return ['clocked_in' => false, 'available' => false];
        }

        $record = \App\Models\AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', today())
            ->latest('clock_in_at')
            ->first();

        return [
            'available' => true,
            'clocked_in' => $record !== null && $record->clock_out_at === null,
            'clock_in_at' => $record?->clock_in_at?->toIso8601String(),
        ];
    }
}
