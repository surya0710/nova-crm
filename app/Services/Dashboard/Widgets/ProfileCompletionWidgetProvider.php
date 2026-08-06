<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\Hrms\EmployeeProfileService;
use Illuminate\Support\Facades\Schema;

class ProfileCompletionWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'profile_completion';
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

        $completion = app(EmployeeProfileService::class)->profileCompletion($employee);

        return [
            'available' => true,
            'percentage' => $completion['percentage'],
            'sections' => $completion['sections'],
            'employee_id' => $employee->id,
        ];
    }
}
