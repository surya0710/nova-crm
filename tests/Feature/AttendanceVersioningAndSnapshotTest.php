<?php

namespace Tests\Feature;

use App\Models\AttendancePeriod;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRecordVersion;
use App\Models\AttendanceSnapshot;
use App\Models\AttendanceSnapshotRow;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\HrmsShift;
use App\Models\Organization;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\Hrms\AttendanceLockService;
use App\Services\Hrms\AttendanceService;
use App\Services\Hrms\AttendanceSnapshotService;
use App\Services\Hrms\AttendanceValidationService;
use App\Services\Hrms\PayrollService;
use App\Services\Hrms\WorkingTimeCalculator;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceVersioningAndSnapshotTest extends TestCase
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

    public function test_working_time_calculator_is_pure_and_handles_overnight(): void
    {
        $calculator = new WorkingTimeCalculator;
        $result = $calculator->calculate(
            Carbon::parse('2026-07-20 22:00:00'),
            Carbon::parse('2026-07-21 06:00:00'),
            Carbon::parse('2026-07-20'),
            [
                'start_time' => '22:00',
                'end_time' => '06:00',
                'break_minutes' => 30,
                'grace_period_minutes' => 10,
                'working_hours' => 7.5,
                'minimum_working_minutes' => 420,
                'overtime_threshold_minutes' => 450,
                'is_overnight' => true,
                'overtime_allowed' => true,
            ]
        );

        $this->assertSame(480, $result['gross_minutes']);
        $this->assertSame(450, $result['working_minutes']);
        $this->assertSame(30, $result['break_minutes']);
        $this->assertSame(0, $result['overtime_minutes']);
        $this->assertSame('present', $result['status']);
    }

    public function test_version_history_is_preserved_on_clock_out_and_correction(): void
    {
        [$organization, $hr, $employee, $shift] = $this->attendanceScenario();
        app(TenantContext::class)->set($organization);
        $service = app(AttendanceService::class);

        Carbon::setTestNow('2026-07-20 09:05:00');
        $service->clockIn($employee, now(), $hr);

        Carbon::setTestNow('2026-07-20 18:00:00');
        $record = $service->clockOut($employee, now(), $hr);

        $this->assertSame(2, $record->version);
        $this->assertDatabaseHas('attendance_record_versions', [
            'attendance_record_id' => $record->id,
            'version' => 1,
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'attendance_version_created']);

        $correction = $service->submitCorrection($record, [
            'requested_clock_in_at' => '2026-07-20 09:00:00',
            'requested_clock_out_at' => '2026-07-20 19:00:00',
            'reason' => 'Forgot late clock-in',
        ], $hr);

        $service->approveCorrection($correction, ['review_notes' => 'OK'], $hr);
        $record->refresh();

        $this->assertSame(3, $record->version);
        $this->assertSame(2, AttendanceRecordVersion::query()->where('attendance_record_id', $record->id)->count());
    }

    public function test_freeze_blocks_employee_edits_and_lock_generates_snapshot(): void
    {
        [$organization, $hr, $employee] = $this->attendanceScenario();
        app(TenantContext::class)->set($organization);
        $attendance = app(AttendanceService::class);
        $lock = app(AttendanceLockService::class);

        Carbon::setTestNow('2026-07-20 09:00:00');
        $attendance->clockIn($employee, now(), $hr);
        Carbon::setTestNow('2026-07-20 18:00:00');
        $attendance->clockOut($employee, now(), $hr);

        $period = $lock->createPeriod([
            'organization_id' => $organization->id,
            'name' => 'July 2026 Attendance',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ], $hr);

        $lock->freeze($period, $hr);
        $this->assertDatabaseHas('audit_logs', ['event' => 'attendance_frozen']);
        $this->assertTrue($period->fresh()->isFrozen());

        try {
            $attendance->clockIn($employee, Carbon::parse('2026-07-21 09:00:00'), $hr);
            $this->fail('Expected frozen period to block clock-in.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('attendance', $e->errors());
        }

        $locked = $lock->lock($period->fresh(), $hr);
        $this->assertTrue($locked->isLocked());
        $this->assertDatabaseHas('audit_logs', ['event' => 'attendance_locked']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'attendance_snapshot_generated']);

        $snapshot = $locked->activeSnapshot();
        $this->assertNotNull($snapshot);
        $this->assertSame(AttendanceSnapshot::STATUS_ACTIVE, $snapshot->status);
        $this->assertGreaterThan(0, $snapshot->record_count);

        $this->expectException(ValidationException::class);
        $attendance->clockOut($employee, Carbon::parse('2026-07-22 18:00:00'), $hr);
    }

    public function test_re_lock_supersedes_previous_snapshot(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);
        $lock = app(AttendanceLockService::class);

        $period = $lock->createPeriod([
            'organization_id' => $organization->id,
            'name' => 'Aug Attendance',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ], $hr);

        $lock->lock($period, $hr);
        $first = $period->fresh()->activeSnapshot();
        $this->assertSame(1, $first->snapshot_version);

        $lock->reopen($period->fresh(), $hr);
        $lock->lock($period->fresh(), $hr);

        $second = $period->fresh()->activeSnapshot();
        $this->assertSame(2, $second->snapshot_version);
        $this->assertSame(AttendanceSnapshot::STATUS_SUPERSEDED, $first->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'attendance_snapshot_superseded']);
    }

    public function test_validation_blocks_lock_on_missing_checkout(): void
    {
        [$organization, $hr, $employee] = $this->attendanceScenario();
        app(TenantContext::class)->set($organization);
        app(AttendanceService::class)->clockIn($employee, Carbon::parse('2026-07-20 09:00:00'), $hr);

        $period = app(AttendanceLockService::class)->createPeriod([
            'organization_id' => $organization->id,
            'name' => 'July',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ], $hr);

        $validation = app(AttendanceValidationService::class)->validatePeriod($period);
        $this->assertFalse($validation['passed']);
        $this->assertSame(
            AttendanceValidationService::ERROR_MISSING_CHECKOUT,
            $validation['errors'][0]['code']
        );

        $this->expectException(ValidationException::class);
        app(AttendanceLockService::class)->lock($period, $hr);
    }

    public function test_payroll_consumes_snapshot_exclusively_and_stays_stable_after_edits(): void
    {
        [$organization, $hr, $employee, $shift] = $this->attendanceScenario();
        app(TenantContext::class)->set($organization);
        $attendance = app(AttendanceService::class);
        $lock = app(AttendanceLockService::class);

        Carbon::setTestNow('2026-07-20 09:00:00');
        $attendance->clockIn($employee, now(), $hr);
        Carbon::setTestNow('2026-07-20 19:00:00');
        $record = $attendance->clockOut($employee, now(), $hr);
        $otBefore = (int) $record->overtime_minutes;

        $payrollPeriod = PayrollPeriod::factory()->open()->create([
            'organization_id' => $organization->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ]);

        $period = $lock->createPeriodForPayroll($payrollPeriod, $hr);
        $lock->lock($period, $hr);

        app(TenantContext::class)->set($organization);
        $context = app(PayrollService::class)->resolveCalculationContext($employee, $payrollPeriod);
        $this->assertSame($otBefore, $context['attendance']['overtime_minutes']);
        $this->assertArrayHasKey('snapshot_id', $context['attendance']);

        // Live edits after lock are blocked; reopen + edit + re-lock creates new snapshot.
        $lock->reopen($period->fresh(), $hr);
        $correction = $attendance->submitCorrection($record->fresh(), [
            'requested_clock_out_at' => '2026-07-20 20:00:00',
            'reason' => 'Extra hour',
        ], $hr);
        $attendance->approveCorrection($correction, [], $hr);
        $record->refresh();
        $this->assertNotSame($otBefore, (int) $record->overtime_minutes);

        // After reopen there is no active locked snapshot — payroll must fail.
        app(TenantContext::class)->set($organization);
        try {
            app(PayrollService::class)->resolveCalculationContext($employee, $payrollPeriod);
            $this->fail('Expected payroll to reject unlocked attendance.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('attendance', $e->errors());
        }

        $lock->lock($period->fresh(), $hr);
        app(TenantContext::class)->set($organization);
        $contextV2 = app(PayrollService::class)->resolveCalculationContext($employee, $payrollPeriod);
        $this->assertSame((int) $record->fresh()->overtime_minutes, $contextV2['attendance']['overtime_minutes']);
        $this->assertSame(2, $contextV2['attendance']['snapshot_version']);
    }

    public function test_reopen_blocked_when_payroll_approved(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);
        $lock = app(AttendanceLockService::class);

        $payrollPeriod = PayrollPeriod::factory()->open()->create([
            'organization_id' => $organization->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ]);
        $period = $lock->createPeriodForPayroll($payrollPeriod, $hr);
        $lock->lock($period, $hr);

        PayrollRun::query()->create([
            'organization_id' => $organization->id,
            'payroll_period_id' => $payrollPeriod->id,
            'status' => 'approved',
            'triggered_by' => $hr->id,
            'engine_version' => 'test',
        ]);

        $this->expectException(ValidationException::class);
        $lock->reopen($period->fresh(), $hr);
    }

    public function test_tenant_isolation_for_periods(): void
    {
        [$organizationA, $hrA] = $this->organizationWithHrUser();
        [$organizationB, $hrB] = $this->organizationWithHrUser();

        app(TenantContext::class)->set($organizationA);
        $period = app(AttendanceLockService::class)->createPeriod([
            'organization_id' => $organizationA->id,
            'name' => 'Org A',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ], $hrA);

        $status = $this->actingAs($hrB)->withSession(['current_organization_id' => $organizationB->id])
            ->get(route('hrms.attendance.periods.show', $period))
            ->status();

        $this->assertTrue(in_array($status, [403, 404], true));
    }

    public function test_http_lock_lifecycle_requires_permission(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);
        $session = ['current_organization_id' => $organization->id];

        $period = app(AttendanceLockService::class)->createPeriod([
            'organization_id' => $organization->id,
            'name' => 'July Period',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ], $hr);

        app(AttendanceLockService::class)->lock($period, $hr);
        $this->assertTrue($period->fresh()->isLocked());

        $employeeUser = User::factory()->create();
        $organization->addMember($employeeUser, 'employee');

        $this->actingAs($employeeUser)->withSession($session)
            ->post(route('hrms.attendance.periods.reopen', $period))
            ->assertForbidden();

        $this->actingAs($employeeUser)->withSession($session)
            ->get(route('hrms.attendance.periods.index'))
            ->assertForbidden();
    }

    public function test_snapshot_rows_are_immutable_inserts(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);
        $lock = app(AttendanceLockService::class);
        $period = $lock->createPeriod([
            'organization_id' => $organization->id,
            'name' => 'Empty Lock',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
        ], $hr);
        $lock->lock($period, $hr);

        $snapshot = $period->fresh()->activeSnapshot();
        $this->assertInstanceOf(AttendanceSnapshot::class, $snapshot);
        $this->assertSame(0, AttendanceSnapshotRow::query()->where('attendance_snapshot_id', $snapshot->id)->count());
        $this->assertSame(0, $snapshot->record_count);
    }

    /** @return array{0: Organization, 1: User, 2: Employee, 3: HrmsShift} */
    private function attendanceScenario(): array
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $shift = HrmsShift::factory()->create([
            'organization_id' => $organization->id,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'break_minutes' => 60,
            'grace_period_minutes' => 15,
            'working_hours' => 8,
            'minimum_working_minutes' => 420,
            'overtime_threshold_minutes' => 480,
        ]);
        EmployeeShiftAssignment::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => '2026-01-01',
        ]);

        return [$organization, $hr, $employee, $shift];
    }

    /** @return array{0: Organization, 1: User} */
    private function organizationWithHrUser(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        return [$organization, $user];
    }
}
