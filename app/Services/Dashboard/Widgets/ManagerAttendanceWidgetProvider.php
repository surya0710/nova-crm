<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\Dashboard\ModuleSubscriptionService;
use App\Services\Hrms\AttendanceDashboardService;
use Illuminate\Support\Facades\Schema;

class ManagerAttendanceWidgetProvider extends AbstractWidgetProvider
{
    public function __construct(
        ModuleSubscriptionService $subscriptionService,
        protected AttendanceDashboardService $attendanceDashboard,
    ) {
        parent::__construct($subscriptionService);
    }

    public function key(): string
    {
        return 'manager_attendance';
    }

    public function subscriptionModule(): ?string
    {
        return 'hrms';
    }

    public function permissionSlug(): ?string
    {
        return 'manager.dashboard';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        if (! Schema::hasTable('attendance_records')) {
            return ['available' => false];
        }

        $manager = Employee::query()->where('user_id', $user->id)->first();
        if (! $manager) {
            return ['available' => false];
        }

        $summary = $this->attendanceDashboard->teamSummary($manager);

        return [
            'available' => true,
            'team_summary' => $summary,
        ];
    }
}
