<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\Hrms\EmployeeProfileService;
use Illuminate\Support\Facades\Schema;

class TeamMembersWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'team_members';
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
        if (! Schema::hasTable('employees')) {
            return ['available' => false, 'members' => []];
        }

        $employee = Employee::query()->where('user_id', $user->id)->first();
        if (! $employee) {
            return ['available' => false, 'members' => []];
        }

        $structure = app(EmployeeProfileService::class)->reportingStructure($employee);
        $members = $structure['direct_reportees']->map(fn (Employee $e) => [
            'id' => $e->id,
            'name' => $e->full_name,
            'designation' => $e->designation?->name,
            'status' => $e->status,
        ])->values();

        return [
            'available' => true,
            'members' => $members,
            'count' => $members->count(),
        ];
    }
}
