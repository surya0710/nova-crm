<?php

namespace Tests\Feature;

use App\Events\AttendanceClockedIn;
use App\Events\AttendanceClockedOut;
use App\Events\AttendanceCorrectionApproved;
use App\Events\AttendanceCorrectionSubmitted;
use App\Events\AttendanceOvertimeRecorded;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\HrmsShift;
use App\Models\Organization;
use App\Models\User;
use App\Services\Hrms\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class HrmsAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-20 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_shift_crud_and_duplicate_code_validation(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.shifts.store'), [
            'name' => 'General',
            'code' => 'GEN',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'grace_period_minutes' => 15,
            'break_minutes' => 60,
            'minimum_working_minutes' => 420,
            'overtime_threshold_minutes' => 480,
        ])->assertRedirect(route('hrms.shifts.index'));

        $this->assertDatabaseHas('hrms_shifts', ['code' => 'GEN', 'organization_id' => $organization->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'shift_created']);

        $shift = HrmsShift::query()->firstOrFail();

        $this->actingAs($hr)->withSession($session)->put(route('hrms.shifts.update', $shift), [
            'name' => 'General Updated',
            'code' => 'GEN',
            'start_time' => '09:00',
            'end_time' => '18:00',
        ])->assertRedirect(route('hrms.shifts.index'));

        $this->assertDatabaseHas('audit_logs', ['event' => 'shift_updated']);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.shifts.store'), [
            'name' => 'Duplicate',
            'code' => 'GEN',
            'start_time' => '09:00',
            'end_time' => '18:00',
        ])->assertSessionHasErrors('code');
    }

    public function test_shift_assignment_and_overlap_validation(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $shift = HrmsShift::factory()->create(['organization_id' => $organization->id]);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.shift-assignments.store'), [
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => '2026-01-01',
        ])->assertRedirect(route('hrms.shift-assignments.index'));

        $this->assertDatabaseHas('employee_shift_assignments', [
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'shift_assigned']);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.shift-assignments.store'), [
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => '2026-06-01',
        ])->assertSessionHasErrors('effective_from');
    }

    public function test_clock_in_and_clock_out_flow(): void
    {
        Event::fake([AttendanceClockedIn::class, AttendanceClockedOut::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = $this->employeeWithShift($organization);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.clock-in'), [
            'employee_id' => $employee->id,
            'clock_in_at' => '2026-07-20 09:00:00',
        ])->assertRedirect(route('hrms.attendance.index'));

        $record = AttendanceRecord::query()->firstOrFail();
        $this->assertSame('pending', $record->status);
        Event::assertDispatched(AttendanceClockedIn::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'attendance_clocked_in']);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.clock-out'), [
            'employee_id' => $employee->id,
            'clock_out_at' => '2026-07-20 18:00:00',
        ])->assertRedirect(route('hrms.attendance.index'));

        $record->refresh();
        $this->assertNotNull($record->clock_out_at);
        Event::assertDispatched(AttendanceClockedOut::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'attendance_clocked_out']);
    }

    public function test_double_clock_in_is_prevented(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = $this->employeeWithShift($organization);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.clock-in'), [
            'employee_id' => $employee->id,
            'clock_in_at' => '2026-07-20 09:00:00',
        ]);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.clock-in'), [
            'employee_id' => $employee->id,
            'clock_in_at' => '2026-07-20 09:05:00',
        ])->assertSessionHasErrors('employee_id');
    }

    public function test_double_clock_out_is_prevented(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = $this->employeeWithShift($organization);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.clock-in'), [
            'employee_id' => $employee->id,
            'clock_in_at' => '2026-07-20 09:00:00',
        ]);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.clock-out'), [
            'employee_id' => $employee->id,
            'clock_out_at' => '2026-07-20 18:00:00',
        ]);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.clock-out'), [
            'employee_id' => $employee->id,
            'clock_out_at' => '2026-07-20 18:30:00',
        ])->assertSessionHasErrors('employee_id');
    }

    public function test_attendance_calculations_for_late_early_and_overtime(): void
    {
        Event::fake([AttendanceOvertimeRecorded::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = $this->employeeWithShift($organization, [
            'grace_period_minutes' => 15,
            'break_minutes' => 60,
            'minimum_working_minutes' => 420,
            'overtime_threshold_minutes' => 480,
        ]);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.clock-in'), [
            'employee_id' => $employee->id,
            'clock_in_at' => '2026-07-20 09:20:00',
        ]);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.clock-out'), [
            'employee_id' => $employee->id,
            'clock_out_at' => '2026-07-20 19:00:00',
        ]);

        $record = AttendanceRecord::query()->firstOrFail();
        $this->assertSame(5, $record->late_minutes);
        $this->assertSame(0, $record->early_departure_minutes);
        $this->assertSame(520, $record->working_minutes);
        $this->assertSame(40, $record->overtime_minutes);
        $this->assertSame('late', $record->status);

        Event::assertDispatched(AttendanceOvertimeRecorded::class);
    }

    public function test_early_departure_is_calculated(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = $this->employeeWithShift($organization);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.clock-in'), [
            'employee_id' => $employee->id,
            'clock_in_at' => '2026-07-20 09:00:00',
        ]);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.clock-out'), [
            'employee_id' => $employee->id,
            'clock_out_at' => '2026-07-20 17:00:00',
        ]);

        $record = AttendanceRecord::query()->firstOrFail();
        $this->assertSame(60, $record->early_departure_minutes);
        $this->assertSame(420, $record->working_minutes);
    }

    public function test_correction_submit_approve_and_audit(): void
    {
        Event::fake([AttendanceCorrectionSubmitted::class, AttendanceCorrectionApproved::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = $this->employeeWithShift($organization);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.clock-in'), [
            'employee_id' => $employee->id,
            'clock_in_at' => '2026-07-20 09:30:00',
        ]);
        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.clock-out'), [
            'employee_id' => $employee->id,
            'clock_out_at' => '2026-07-20 18:00:00',
        ]);

        $record = AttendanceRecord::query()->firstOrFail();

        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.corrections.store'), [
            'attendance_record_id' => $record->id,
            'requested_clock_in_at' => '2026-07-20 09:00:00',
            'requested_clock_out_at' => '2026-07-20 18:00:00',
            'reason' => 'Forgot to clock in on time',
        ])->assertRedirect(route('hrms.attendance.corrections.index'));

        $correction = AttendanceCorrection::query()->firstOrFail();
        Event::assertDispatched(AttendanceCorrectionSubmitted::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'attendance_correction_submitted']);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.corrections.approve', $correction), [
            'review_notes' => 'Approved',
        ])->assertRedirect(route('hrms.attendance.corrections.index'));

        $record->refresh();
        $correction->refresh();
        $this->assertSame('approved', $correction->status);
        $this->assertSame(0, $record->late_minutes);
        $this->assertDatabaseHas('audit_logs', ['event' => 'attendance_correction_approved']);
        Event::assertDispatched(AttendanceCorrectionApproved::class);
    }

    public function test_correction_can_be_rejected(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = $this->employeeWithShift($organization);
        $session = ['current_organization_id' => $organization->id];
        $record = $this->seedAttendanceRecord($organization, $employee, $hr, $session);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.corrections.store'), [
            'attendance_record_id' => $record->id,
            'reason' => 'Wrong entry',
        ]);

        $correction = AttendanceCorrection::query()->firstOrFail();

        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.corrections.reject', $correction))
            ->assertRedirect(route('hrms.attendance.corrections.index'));

        $this->assertSame('rejected', $correction->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'attendance_correction_rejected']);
    }

    public function test_cross_organization_attendance_access_is_forbidden(): void
    {
        [$orgA, $hrA] = $this->organizationWithHrUser();
        [$orgB] = $this->organizationWithHrUser();
        $employeeB = Employee::factory()->create(['organization_id' => $orgB->id]);
        $recordB = AttendanceRecord::factory()->create([
            'organization_id' => $orgB->id,
            'employee_id' => $employeeB->id,
        ]);

        $this->actingAs($hrA)->withSession(['current_organization_id' => $orgA->id])
            ->get(route('hrms.attendance.show', $recordB))
            ->assertForbidden();
    }

    public function test_manager_can_view_but_not_manage_attendance(): void
    {
        [$organization] = $this->organizationWithHrUser();
        $manager = User::factory()->create();
        $organization->addMember($manager, 'manager');
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($manager)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.attendance.index'))
            ->assertOk();

        $this->actingAs($manager)->withSession(['current_organization_id' => $organization->id])
            ->post(route('hrms.attendance.clock-in'), [
                'employee_id' => $employee->id,
            ])->assertForbidden();
    }

    public function test_unauthorized_user_cannot_access_attendance(): void
    {
        [$organization] = $this->organizationWithHrUser();
        $user = User::factory()->create();
        $organization->addMember($user, 'employee');

        $this->actingAs($user)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.attendance.index'))
            ->assertForbidden();
    }

    public function test_daily_summary_returns_counts(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = $this->employeeWithShift($organization);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.clock-in'), [
            'employee_id' => $employee->id,
            'clock_in_at' => '2026-07-20 09:00:00',
        ]);
        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.clock-out'), [
            'employee_id' => $employee->id,
            'clock_out_at' => '2026-07-20 18:00:00',
        ]);

        $summary = app(AttendanceService::class)->dailySummary(Carbon::parse('2026-07-20'));
        $this->assertSame(1, $summary['total_employees']);
        $this->assertSame(1, $summary['present'] + $summary['late']);
    }

    private function employeeWithShift(Organization $organization, array $shiftOverrides = []): Employee
    {
        $employee = Employee::factory()->create(['organization_id' => $organization->id, 'status' => 'active']);
        $shift = HrmsShift::factory()->create(array_merge([
            'organization_id' => $organization->id,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'grace_period_minutes' => 15,
            'break_minutes' => 60,
            'minimum_working_minutes' => 420,
            'overtime_threshold_minutes' => 480,
        ], $shiftOverrides));

        EmployeeShiftAssignment::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => '2026-01-01',
        ]);

        return $employee;
    }

    /** @return array{0: Organization, 1: User} */
    private function organizationWithHrUser(): array
    {
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        return [$organization, $user];
    }

    /** @param array<string, mixed> $session */
    private function seedAttendanceRecord(Organization $organization, Employee $employee, User $hr, array $session): AttendanceRecord
    {
        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.clock-in'), [
            'employee_id' => $employee->id,
            'clock_in_at' => '2026-07-20 09:00:00',
        ]);
        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.clock-out'), [
            'employee_id' => $employee->id,
            'clock_out_at' => '2026-07-20 18:00:00',
        ]);

        return AttendanceRecord::query()->firstOrFail();
    }
}
