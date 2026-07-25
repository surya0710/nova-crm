<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\User;
use App\Services\ResourceAllocationService;
use App\Services\ResourceCalendarService;
use App\Services\TenantContext;
use App\Services\WorkloadService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkloadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_utilization_calculation_with_allocation_and_calendar(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create(['organization_id' => $organization->id]);

        app(ResourceCalendarService::class)->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'working_hours_per_day' => 8,
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'effective_from' => '2026-07-01',
        ]);

        app(ResourceAllocationService::class)->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'allocation_type' => 'internal',
            'allocation_percentage' => 50,
            'planned_start_date' => '2026-07-20',
            'planned_end_date' => '2026-07-24',
        ], $user);

        $result = app(WorkloadService::class)->calculateForEmployee(
            $employee,
            Carbon::parse('2026-07-20'),
            Carbon::parse('2026-07-24'),
        );

        $this->assertSame(40.0, $result['capacity']);
        $this->assertSame(40.0, $result['available']);
        $this->assertSame(20.0, $result['allocated']);
        $this->assertSame(50.0, $result['utilization']);
        $this->assertSame('optimal', $result['status']);
        $this->assertCount(5, $result['days']);
    }

    public function test_approved_leave_reduces_available_hours(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create(['organization_id' => $organization->id]);

        app(ResourceCalendarService::class)->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'working_hours_per_day' => 8,
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'effective_from' => '2026-07-01',
        ]);

        $leaveType = LeaveType::factory()->create(['organization_id' => $organization->id]);
        LeaveApplication::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-07-22',
            'end_date' => '2026-07-22',
            'days' => 1,
            'status' => 'approved',
        ]);

        app(ResourceAllocationService::class)->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'allocation_type' => 'internal',
            'allocation_percentage' => 50,
            'planned_start_date' => '2026-07-20',
            'planned_end_date' => '2026-07-24',
        ], $user);

        $result = app(WorkloadService::class)->calculateForEmployee(
            $employee,
            Carbon::parse('2026-07-20'),
            Carbon::parse('2026-07-24'),
        );

        $this->assertSame(40.0, $result['capacity']);
        $this->assertSame(32.0, $result['available']);
        $this->assertSame(20.0, $result['allocated']);
        $this->assertSame(62.5, $result['utilization']);
    }
}
