<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\Hrms\EmployeeProfileService;
use Illuminate\Support\Facades\Schema;

class UpcomingHolidaysWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'upcoming_holidays';
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
        if (! Schema::hasTable('holidays')) {
            return ['available' => false, 'holidays' => []];
        }

        $employee = Employee::query()->where('user_id', $user->id)->first();
        if (! $employee) {
            return ['available' => false, 'holidays' => []];
        }

        $holidays = app(EmployeeProfileService::class)->upcomingHolidays($employee)
            ->map(fn ($h) => [
                'id' => $h->id,
                'name' => $h->name,
                'date' => optional($h->holiday_date)->toDateString(),
            ])
            ->values();

        return [
            'available' => true,
            'holidays' => $holidays,
        ];
    }
}
