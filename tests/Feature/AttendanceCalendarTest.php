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
use App\Services\Hrms\AttendanceCalendarService;
use App\Services\Hrms\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-07-15 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_hrms_attendance_index_renders_calendar(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        Employee::factory()->create(['organization_id' => $organization->id, 'status' => 'active']);

        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.attendance.index', ['year' => 2026, 'month' => 7]))
            ->assertOk()
            ->assertSee('Attendance Calendar')
            ->assertSee('Present')
            ->assertSee('July 2026');
    }

    public function test_ess_attendance_index_renders_calendar(): void
    {
        [$organization, $user, $employee] = $this->employeeSetup();

        $this->actingAs($user)->withSession(['current_organization_id' => $organization->id])
            ->get(route('ess.attendance.index', ['year' => 2026, 'month' => 7]))
            ->assertOk()
            ->assertSee('My Attendance')
            ->assertSee('July 2026');
    }

    public function test_calendar_marks_present_holiday_weekend_and_leave(): void
    {
        [$organization, $user, $employee] = $this->employeeSetup();
        $service = app(AttendanceCalendarService::class);

        AttendanceRecord::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'attendance_date' => '2026-07-14',
            'status' => 'present',
            'clock_in_at' => '2026-07-14 09:00:00',
            'clock_out_at' => '2026-07-14 18:00:00',
        ]);

        Holiday::factory()->create([
            'organization_id' => $organization->id,
            'holiday_date' => '2026-07-16',
            'name' => 'Independence Day',
        ]);

        $leaveType = LeaveType::factory()->create(['organization_id' => $organization->id]);
        LeaveApplication::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'approved',
            'start_date' => '2026-07-17',
            'end_date' => '2026-07-17',
            'days' => 1,
        ]);

        $calendar = $service->monthForEmployee($employee, 2026, 7);
        $days = collect($calendar['days'])->keyBy('date');

        $this->assertSame('present', $days['2026-07-14']['visual']['key']);
        $this->assertSame('holiday', $days['2026-07-16']['visual']['key']);
        $this->assertSame('leave_approved', $days['2026-07-17']['visual']['key']);
        $this->assertSame('weekend', $days['2026-07-19']['visual']['key']); // Sunday
        $this->assertGreaterThan(0, $calendar['summary']['present']);
        $this->assertGreaterThan(0, $calendar['summary']['holiday']);
    }

    public function test_calendar_api_returns_month_payload(): void
    {
        [$organization, $user, $employee] = $this->employeeSetup();
        Sanctum::actingAs($user);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/v1/attendance/calendar?year=2026&month=7')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'year',
                    'month',
                    'days',
                    'summary',
                    'leave_balances',
                    'timeline',
                    'legend',
                ],
            ]);
    }

    public function test_hr_can_filter_calendar_by_employee(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id, 'status' => 'active']);

        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.attendance.index', ['year' => 2026, 'month' => 7, 'employee_id' => $employee->id]))
            ->assertOk()
            ->assertSee($employee->full_name);
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

        return [$organization, $user, $employee];
    }

    /** @return array{0: Organization, 1: User} */
    protected function organizationWithHrUser(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        return [$organization, $user];
    }
}
