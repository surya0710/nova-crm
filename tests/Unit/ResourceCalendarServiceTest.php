<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\ResourceCalendarService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceCalendarServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_working_hours_fall_back_to_config_default_without_calendar(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);

        $monday = Carbon::parse('2026-07-20');
        $service = app(ResourceCalendarService::class);

        $this->assertSame(
            (float) config('resources.default_working_hours_per_day', 8),
            $service->workingHoursForDay($employee, $monday)
        );
        $this->assertSame(0.0, $service->workingHoursForDay($employee, Carbon::parse('2026-07-19')));
    }

    public function test_resolve_for_employee_uses_effective_calendar_hours(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $service = app(ResourceCalendarService::class);

        $calendar = $service->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'working_hours_per_day' => 6,
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'effective_from' => '2026-07-01',
            'effective_to' => null,
        ]);

        $monday = Carbon::parse('2026-07-20');

        $this->assertTrue($calendar->is($service->resolveForEmployee($employee, $monday)));
        $this->assertSame(6.0, $service->workingHoursForDay($employee, $monday));
    }

    public function test_create_calendar_persists_for_employee(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $service = app(ResourceCalendarService::class);

        $calendar = $service->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'working_hours_per_day' => 7.5,
            'working_days' => ['monday', 'wednesday', 'friday'],
            'timezone' => 'UTC',
            'effective_from' => '2026-08-01',
        ]);

        $this->assertDatabaseHas('resource_calendars', [
            'id' => $calendar->id,
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'working_hours_per_day' => 7.5,
        ]);
        $this->assertSame(['monday', 'wednesday', 'friday'], $calendar->working_days);
    }
}
