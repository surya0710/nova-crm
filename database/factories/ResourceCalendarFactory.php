<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\ResourceCalendar;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ResourceCalendar> */
class ResourceCalendarFactory extends Factory
{
    protected $model = ResourceCalendar::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'employee_id' => Employee::factory(),
            'working_hours_per_day' => config('resources.default_working_hours_per_day', 8),
            'working_days' => config('resources.default_working_days', config('hrms.working_days')),
            'timezone' => 'UTC',
            'effective_from' => now()->startOfMonth()->toDateString(),
            'effective_to' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ResourceCalendar $calendar) {
            $this->alignOrganization($calendar);
        })->afterCreating(function (ResourceCalendar $calendar) {
            $this->alignOrganization($calendar, true);
        });
    }

    protected function alignOrganization(ResourceCalendar $calendar, bool $persist = false): void
    {
        $employee = $calendar->employee_id
            ? Employee::query()->find($calendar->employee_id)
            : null;

        if ($employee && (int) $calendar->organization_id !== (int) $employee->organization_id) {
            $calendar->organization_id = $employee->organization_id;

            if ($persist) {
                $calendar->saveQuietly();
            }
        }
    }
}
