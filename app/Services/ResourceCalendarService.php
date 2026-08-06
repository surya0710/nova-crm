<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\ResourceCalendar;
use App\Models\User;
use App\Services\Hrms\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResourceCalendarService
{
    public function __construct(
        protected ?AttendanceService $attendanceService = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): ResourceCalendar
    {
        return DB::transaction(function () use ($data) {
            $organizationId = (int) ($data['organization_id'] ?? app(TenantContext::class)->id());
            $employee = $this->resolveEmployee((int) $data['employee_id'], $organizationId);

            $payload = $this->normalizePayload($data, $organizationId, $employee);

            $this->assertNoOverlappingEffectiveWindow(
                $organizationId,
                (int) $employee->id,
                Carbon::parse($payload['effective_from']),
                isset($payload['effective_to']) ? Carbon::parse($payload['effective_to']) : null,
            );

            return ResourceCalendar::query()->create($payload);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ResourceCalendar $calendar, array $data, ?User $actor = null): ResourceCalendar
    {
        return DB::transaction(function () use ($calendar, $data) {
            $organizationId = (int) $calendar->organization_id;
            $employeeId = (int) ($data['employee_id'] ?? $calendar->employee_id);
            $employee = $this->resolveEmployee($employeeId, $organizationId);

            $payload = $this->normalizePayload(
                [...$calendar->only([
                    'working_hours_per_day',
                    'working_days',
                    'timezone',
                    'effective_from',
                    'effective_to',
                ]), ...$data],
                $organizationId,
                $employee,
            );

            $this->assertNoOverlappingEffectiveWindow(
                $organizationId,
                (int) $employee->id,
                Carbon::parse($payload['effective_from']),
                isset($payload['effective_to']) ? Carbon::parse($payload['effective_to']) : null,
                $calendar->id,
            );

            $calendar->update($payload);

            return $calendar->fresh(['employee']);
        });
    }

    public function delete(ResourceCalendar $calendar): void
    {
        $calendar->delete();
    }

    public function resolveForEmployee(Employee $employee, Carbon $date): ?ResourceCalendar
    {
        return ResourceCalendar::query()
            ->where('organization_id', $employee->organization_id)
            ->where('employee_id', $employee->id)
            ->whereDate('effective_from', '<=', $date->toDateString())
            ->where(function ($query) use ($date): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date->toDateString());
            })
            ->orderByDesc('effective_from')
            ->first();
    }

    public function workingHoursForDay(Employee $employee, Carbon $date): float
    {
        $calendar = $this->resolveForEmployee($employee, $date);
        $workingDays = $this->workingDaysForEmployee($employee, $date);

        if (! in_array(strtolower($date->englishDayOfWeek), $workingDays, true)) {
            return 0.0;
        }

        if ($calendar) {
            return (float) $calendar->working_hours_per_day;
        }

        $shift = $this->attendance()->resolveShiftForEmployee($employee, $date);
        if ($shift && $shift->working_hours !== null) {
            return (float) $shift->working_hours;
        }

        return (float) config('resources.default_working_hours_per_day', 8);
    }

    /**
     * @return list<string>
     */
    public function workingDaysForEmployee(Employee $employee, ?Carbon $date = null): array
    {
        $date ??= now();
        $calendar = $this->resolveForEmployee($employee, $date);

        if ($calendar && is_array($calendar->working_days) && $calendar->working_days !== []) {
            return array_values(array_map('strtolower', $calendar->working_days));
        }

        return $this->defaultWorkingDays($employee);
    }

    public function seedDefaultCalendar(Employee $employee): ResourceCalendar
    {
        $effectiveFrom = $employee->joining_date?->toDateString() ?? now()->toDateString();

        return ResourceCalendar::query()->firstOrCreate(
            [
                'organization_id' => $employee->organization_id,
                'employee_id' => $employee->id,
                'effective_from' => $effectiveFrom,
            ],
            [
                'working_hours_per_day' => config('resources.default_working_hours_per_day', 8),
                'working_days' => $this->defaultWorkingDays($employee),
                'timezone' => $employee->organization?->timezone,
                'effective_to' => null,
            ]
        );
    }

    /**
     * @return list<string>
     */
    public function defaultWorkingDays(Employee $employee): array
    {
        $organization = $employee->organization
            ?? Organization::query()->find($employee->organization_id);

        $workingDays = $organization?->settings['working_days']
            ?? config('hrms.working_days')
            ?? config('resources.default_working_days', []);

        return array_values(array_map('strtolower', (array) $workingDays));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizePayload(array $data, int $organizationId, Employee $employee): array
    {
        $effectiveFrom = Carbon::parse($data['effective_from'] ?? now())->startOfDay();
        $effectiveTo = isset($data['effective_to']) && $data['effective_to'] !== null && $data['effective_to'] !== ''
            ? Carbon::parse($data['effective_to'])->startOfDay()
            : null;

        if ($effectiveTo && $effectiveTo->lt($effectiveFrom)) {
            throw ValidationException::withMessages([
                'effective_to' => __('Effective end date must be on or after the start date.'),
            ]);
        }

        $workingDays = array_values(array_map(
            'strtolower',
            (array) ($data['working_days'] ?? $this->defaultWorkingDays($employee))
        ));

        if ($workingDays === []) {
            throw ValidationException::withMessages([
                'working_days' => __('At least one working day is required.'),
            ]);
        }

        $hours = (float) ($data['working_hours_per_day'] ?? config('resources.default_working_hours_per_day', 8));

        if ($hours <= 0) {
            throw ValidationException::withMessages([
                'working_hours_per_day' => __('Working hours per day must be greater than zero.'),
            ]);
        }

        return [
            'organization_id' => $organizationId,
            'employee_id' => $employee->id,
            'working_hours_per_day' => $hours,
            'working_days' => $workingDays,
            'timezone' => $data['timezone'] ?? $employee->organization?->timezone,
            'effective_from' => $effectiveFrom->toDateString(),
            'effective_to' => $effectiveTo?->toDateString(),
        ];
    }

    protected function resolveEmployee(int $employeeId, int $organizationId): Employee
    {
        $employee = Employee::query()
            ->withoutGlobalScopes()
            ->whereKey($employeeId)
            ->first();

        if (! $employee || (int) $employee->organization_id !== $organizationId) {
            throw ValidationException::withMessages([
                'employee_id' => __('The selected employee does not belong to this organization.'),
            ]);
        }

        return $employee;
    }

    protected function assertNoOverlappingEffectiveWindow(
        int $organizationId,
        int $employeeId,
        Carbon $from,
        ?Carbon $to,
        ?int $ignoreId = null,
    ): void {
        $overlap = ResourceCalendar::query()
            ->where('organization_id', $organizationId)
            ->where('employee_id', $employeeId)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where(function ($query) use ($from, $to): void {
                $query->where(function ($inner) use ($from, $to): void {
                    $inner->whereDate('effective_from', '<=', $to?->toDateString() ?? '9999-12-31')
                        ->where(function ($window) use ($from): void {
                            $window->whereNull('effective_to')
                                ->orWhereDate('effective_to', '>=', $from->toDateString());
                        });
                });
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'effective_from' => __('This calendar overlaps an existing effective window for the employee.'),
            ]);
        }
    }

    protected function attendance(): AttendanceService
    {
        return $this->attendanceService ??= app(AttendanceService::class);
    }
}
