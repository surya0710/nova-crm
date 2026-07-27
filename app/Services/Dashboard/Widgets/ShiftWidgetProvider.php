<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\Dashboard\ModuleSubscriptionService;
use App\Services\Hrms\AttendanceDashboardService;
use Illuminate\Support\Facades\Schema;

class ShiftWidgetProvider extends AbstractWidgetProvider
{
    public function __construct(
        ModuleSubscriptionService $subscriptionService,
        protected AttendanceDashboardService $attendanceDashboard,
    ) {
        parent::__construct($subscriptionService);
    }

    public function key(): string
    {
        return 'shift_information';
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
            return ['available' => false];
        }

        $employee = Employee::query()->where('user_id', $user->id)->first();
        if (! $employee) {
            return ['available' => false];
        }

        $summary = $this->attendanceDashboard->employeeSummary($employee);

        return [
            'available' => $summary['shift_info']['available'] ?? false,
            'shift_info' => $summary['shift_info'],
        ];
    }
}
