<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\User;
use App\Services\Hrms\AttendanceReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
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

    public function test_attendance_reports_page_renders(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        Employee::factory()->create(['organization_id' => $organization->id, 'status' => 'active']);

        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.attendance.reports.index', ['year' => 2026, 'month' => 7]))
            ->assertOk()
            ->assertSee('Attendance Reports')
            ->assertSee('Monthly Attendance');
    }

    public function test_monthly_attendance_report_includes_summary_rows(): void
    {
        [$organization, $hr, $employee] = $this->setupWithEmployee();

        AttendanceRecord::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'attendance_date' => '2026-07-14',
            'status' => 'present',
            'clock_in_at' => '2026-07-14 09:00:00',
            'clock_out_at' => '2026-07-14 18:00:00',
        ]);

        $report = app(AttendanceReportService::class)->compile('monthly_attendance', [
            'year' => 2026,
            'month' => 7,
            'employee_id' => $employee->id,
        ]);

        $this->assertSame('monthly_attendance', $report['report_type']);
        $this->assertCount(1, $report['rows']);
        $this->assertGreaterThan(0, $report['rows'][0]['present']);
    }

    public function test_late_report_lists_late_records(): void
    {
        [$organization, $hr, $employee] = $this->setupWithEmployee();

        AttendanceRecord::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'attendance_date' => '2026-07-14',
            'status' => 'late',
            'late_minutes' => 25,
            'clock_in_at' => '2026-07-14 09:25:00',
            'clock_out_at' => '2026-07-14 18:00:00',
        ]);

        $report = app(AttendanceReportService::class)->compile('late_report', [
            'year' => 2026,
            'month' => 7,
            'employee_id' => $employee->id,
        ]);

        $this->assertCount(1, $report['rows']);
        $this->assertSame(25, $report['rows'][0]['late_minutes']);
    }

    public function test_leave_summary_report_includes_applications(): void
    {
        [$organization, $hr, $employee] = $this->setupWithEmployee();
        $leaveType = LeaveType::factory()->create(['organization_id' => $organization->id]);
        LeaveApplication::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'approved',
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-21',
            'days' => 2,
        ]);

        $report = app(AttendanceReportService::class)->compile('leave_summary', [
            'year' => 2026,
            'month' => 7,
            'employee_id' => $employee->id,
        ]);

        $this->assertCount(1, $report['rows']);
        $this->assertSame(2.0, $report['rows'][0]['days']);
    }

    public function test_csv_export_downloads(): void
    {
        [$organization, $hr, $employee] = $this->setupWithEmployee();

        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.attendance.reports.export', [
                'report_type' => 'monthly_attendance',
                'year' => 2026,
                'month' => 7,
                'employee_id' => $employee->id,
                'format' => 'csv',
            ]))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_department_filter_scopes_report(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $department = Department::factory()->create(['organization_id' => $organization->id]);
        $inDept = Employee::factory()->create([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'status' => 'active',
        ]);
        Employee::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        $report = app(AttendanceReportService::class)->compile('monthly_attendance', [
            'year' => 2026,
            'month' => 7,
            'department_id' => $department->id,
        ]);

        $this->assertCount(1, $report['rows']);
        $this->assertSame($inDept->employee_code, $report['rows'][0]['employee_code']);
    }

    /** @return array{0: Organization, 1: User} */
    protected function organizationWithHrUser(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        return [$organization, $user];
    }

    /** @return array{0: Organization, 1: User, 2: Employee} */
    protected function setupWithEmployee(): array
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        return [$organization, $hr, $employee];
    }
}
