<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\Hrms\EmployeeProfileService;
use Illuminate\Support\Facades\Schema;

class ReportingManagerWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'reporting_manager';
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

        $structure = app(EmployeeProfileService::class)->reportingStructure($employee);

        return [
            'available' => true,
            'manager' => $structure['reporting_manager'] ? [
                'id' => $structure['reporting_manager']->id,
                'name' => $structure['reporting_manager']->full_name,
                'designation' => $structure['reporting_manager']->designation?->name,
            ] : null,
            'department_head' => $structure['department_head'] ? [
                'id' => $structure['department_head']->id,
                'name' => $structure['department_head']->full_name,
            ] : null,
            'hr_contact' => $structure['hr_contact'] ? [
                'id' => $structure['hr_contact']->id,
                'name' => $structure['hr_contact']->full_name,
            ] : null,
        ];
    }
}
