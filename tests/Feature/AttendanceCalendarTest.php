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
            ->assertSee('Attendance')
            ->assertSee('Present')
            ->assertSee('Today')
            ->assertSee('attendanceCalendar');
    }

    public function test_ess_attendance_index_renders_calendar(): void
    {
        [$organization, $user, $employee] = $this->employeeSetup();

        $this->actingAs($user)->withSession(['current_organization_id' => $organization->id])
            ->get(route('ess.attendance.index', ['year' => 2026, 'month' => 7]))
            ->assertOk()
            ->assertSee('My Attendance')
            ->assertSee('July 2026')
            ->assertSee('Today');
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

    public function test_calendar_navigation_bounds_are_configurable(): void
    {
        config([
            'hrms.attendance_calendar.year_range_before' => 5,
            'hrms.attendance_calendar.year_range_after' => 5,
        ]);

        $service = app(AttendanceCalendarService::class);
        $navigation = $service->navigationConfig(2026);

        $this->assertSame(2021, $navigation['min_year']);
        $this->assertSame(2031, $navigation['max_year']);
        $this->assertCount(11, $navigation['years']);

        [$year, $month] = array_values($service->normalizeYearMonth(2010, 13));
        $this->assertSame(2021, $year);
        $this->assertSame(12, $month);
    }

    public function test_calendar_api_includes_navigation_metadata(): void
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
                    'navigation' => ['min_year', 'max_year', 'years', 'today_year', 'today_month'],
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

    public function test_calendar_api_includes_day_timeline(): void
    {
        [$organization, $user, $employee] = $this->employeeSetup();

        AttendanceRecord::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'attendance_date' => '2026-07-14',
            'status' => 'present',
            'clock_in_at' => '2026-07-14 09:00:00',
            'clock_out_at' => '2026-07-14 18:00:00',
        ]);

        $calendar = app(AttendanceCalendarService::class)->monthForEmployee($employee, 2026, 7);
        $day = collect($calendar['days'])->firstWhere('date', '2026-07-14');

        $this->assertNotEmpty($day['timeline']);
        $this->assertTrue(collect($day['timeline'])->contains(fn (array $entry) => $entry['type'] === 'check_in'));
        $this->assertTrue(collect($day['timeline'])->contains(fn (array $entry) => $entry['type'] === 'check_out'));
    }

    public function test_manager_can_open_direct_report_calendar(): void
    {
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $managerUser = User::factory()->create();
        $organization->addMember($managerUser, 'manager');
        $manager = Employee::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $managerUser->id,
            'status' => 'active',
        ]);
        $report = Employee::factory()->create([
            'organization_id' => $organization->id,
            'reporting_manager_id' => $manager->id,
            'status' => 'active',
        ]);

        $this->actingAs($managerUser)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.attendance.index', [
                'year' => 2026,
                'month' => 7,
                'employee_id' => $report->id,
            ]))
            ->assertOk()
            ->assertSee($report->full_name);
    }

    public function test_hr_calendar_supports_department_filter(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $department = \App\Models\Department::factory()->create(['organization_id' => $organization->id]);
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'status' => 'active',
        ]);

        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.attendance.index', [
                'year' => 2026,
                'month' => 7,
                'department_id' => $department->id,
                'employee_id' => $employee->id,
            ]))
            ->assertOk()
            ->assertSee($department->name)
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
