<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\HrmsShift;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\User;
use App\Services\Hrms\AttendanceDashboardService;
use App\Services\Hrms\AttendanceService;
use App\Services\Hrms\LeaveService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-07-21 09:30:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @return array{0: Organization, 1: User, 2: Employee}
     */
    protected function employeeSetup(): array
    {
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $user = User::factory()->create();
        $organization->addMember($user, 'employee');
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);
        app(TenantContext::class)->set($organization);

        return [$organization, $user, $employee];
    }

    public function test_ess_dashboard_shows_check_in_and_working_hours(): void
    {
        [$organization, $user, $employee] = $this->employeeSetup();
        $shift = HrmsShift::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'General Shift',
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);
        app(AttendanceService::class)->assignShift($employee, [
            'shift_id' => $shift->id,
            'effective_from' => '2026-07-01',
        ], $user);

        $this->actingAs($user)->withSession(['current_organization_id' => $organization->id])
            ->get(route('ess.dashboard'))
            ->assertOk()
            ->assertSee('Check In')
            ->assertSee('General Shift');
    }

    public function test_employee_can_check_in_from_dashboard(): void
    {
        [$organization, $user, $employee] = $this->employeeSetup();

        $this->actingAs($user)->withSession(['current_organization_id' => $organization->id])
            ->post(route('ess.attendance.clock-in'), [
                'redirect_to' => route('ess.dashboard'),
            ])
            ->assertRedirect(route('ess.dashboard'));

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $employee->id,
        ]);
    }

    public function test_duplicate_check_in_is_prevented(): void
    {
        [$organization, $user, $employee] = $this->employeeSetup();
        $service = app(AttendanceService::class);
        $service->clockIn($employee, now(), $user);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->clockIn($employee, now(), $user);
    }

    public function test_check_in_blocked_on_approved_leave(): void
    {
        [$organization, $user, $employee] = $this->employeeSetup();
        $leaveType = LeaveType::factory()->create(['organization_id' => $organization->id]);
        LeaveApplication::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'approved',
            'start_date' => '2026-07-21',
            'end_date' => '2026-07-21',
            'days' => 1,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(AttendanceService::class)->clockIn($employee, now(), $user);
    }

    public function test_check_in_blocked_on_holiday(): void
    {
        [$organization, $user, $employee] = $this->employeeSetup();
        Holiday::factory()->create([
            'organization_id' => $organization->id,
            'holiday_date' => '2026-07-21',
            'name' => 'Company Day',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(AttendanceService::class)->clockIn($employee, now(), $user);
    }

    public function test_working_hours_calculated_on_check_out(): void
    {
        [$organization, $user, $employee] = $this->employeeSetup();
        $shift = HrmsShift::factory()->create([
            'organization_id' => $organization->id,
            'start_time' => '09:00',
            'end_time' => '18:00',
            'break_minutes' => 60,
            'grace_period_minutes' => 5,
            'working_hours' => 8,
        ]);
        $service = app(AttendanceService::class);
        $service->assignShift($employee, [
            'shift_id' => $shift->id,
            'effective_from' => '2026-07-01',
        ], $user);

        $service->clockIn($employee, Carbon::parse('2026-07-21 09:04:00'), $user);
        $record = $service->clockOut($employee, Carbon::parse('2026-07-21 18:00:00'), $user);

        $this->assertGreaterThan(0, $record->working_minutes);
        $this->assertSame('late', $record->status);
    }

    public function test_attendance_dashboard_service_returns_summary(): void
    {
        [$organization, $user, $employee] = $this->employeeSetup();
        app(AttendanceService::class)->clockIn($employee, now(), $user);

        $summary = app(AttendanceDashboardService::class)->employeeSummary($employee);

        $this->assertSame('checked_in', $summary['state']);
        $this->assertTrue($summary['working_hours']['is_live']);
        $this->assertTrue($summary['actions']['can_check_out']);
    }

    public function test_manager_team_summary_kpis(): void
    {
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $managerUser = User::factory()->create();
        $organization->addMember($managerUser, 'manager');
        $manager = Employee::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $managerUser->id,
            'status' => 'active',
        ]);
        $member = Employee::factory()->create([
            'organization_id' => $organization->id,
            'reporting_manager_id' => $manager->id,
            'status' => 'active',
        ]);
        app(AttendanceService::class)->clockIn($member, now(), $managerUser);

        $summary = app(AttendanceDashboardService::class)->teamSummary($manager);

        $this->assertSame(1, $summary['team_count']);
        $this->assertSame(1, $summary['working']);
    }

    public function test_api_dashboard_endpoint(): void
    {
        [$organization, $user] = $this->employeeSetup();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/attendance/dashboard', [
            'X-Organization-Id' => (string) $organization->id,
        ])->assertOk()
            ->assertJsonPath('data.state', 'not_checked_in');
    }
}
