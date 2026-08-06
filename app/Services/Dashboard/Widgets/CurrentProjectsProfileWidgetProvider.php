<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\Hrms\EmployeeProfileService;
use Illuminate\Support\Facades\Schema;

class CurrentProjectsProfileWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'current_projects_profile';
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
        if (! Schema::hasTable('employees')) {
            return ['available' => false];
        }

        $employee = Employee::query()->where('user_id', $user->id)->first();
        if (! $employee) {
            return ['available' => false];
        }

        $work = app(EmployeeProfileService::class)->currentWorkSummary($employee);

        return [
            'available' => true,
            'projects' => $work['projects']->map(fn ($m) => [
                'id' => $m->project_id,
                'name' => $m->project?->name,
                'code' => $m->project?->code,
                'status' => $m->project?->status,
                'role' => $m->project_role_label ?? null,
            ])->values(),
            'open_tasks' => $work['open_tasks'],
            'hours_logged_this_week' => $work['hours_logged_this_week'],
            'current_sprint' => $work['current_sprint']?->name,
        ];
    }
}
