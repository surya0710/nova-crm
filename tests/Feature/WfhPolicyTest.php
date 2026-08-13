<?php

namespace Tests\Feature;

use App\Models\AttendanceGeofence;
use App\Models\AttendanceRecord;
use App\Models\AttendanceVerificationAudit;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\EmployeeWfhAssignment;
use App\Models\HrmsShift;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\User;
use App\Models\WfhRequest;
use App\Services\Hrms\AttendanceService;
use App\Services\Hrms\EmployeeService;
use App\Services\Hrms\WfhPolicyService;
use App\Services\Hrms\WfhRequestService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WfhPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-12 09:00:00'); // Wednesday
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_permanent_wfh_assignment_resolves_and_bypasses_geofence(): void
    {
        [$organization, $hr, $employee] = $this->wfhScenario([
            'attendance_verification_mode' => 'geofence',
        ], [
            'enabled' => true,
            'bypass_geofence' => true,
            'record_gps_when_wfh' => false,
            'requires_approval' => true,
        ]);
        app(TenantContext::class)->set($organization);

        AttendanceGeofence::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'HQ',
            'latitude' => 12.9716000,
            'longitude' => 77.5946000,
            'radius_meters' => 100,
        ]);

        app(WfhPolicyService::class)->assign($employee, [
            'policy_type' => 'permanent',
            'effective_from' => '2026-08-01',
            'reason' => 'Remote hire',
        ], $hr);

        $resolution = app(WfhPolicyService::class)->resolveForDate($employee, now());
        $this->assertTrue($resolution['is_wfh']);
        $this->assertSame('permanent', $resolution['policy_type']);

        $record = app(AttendanceService::class)->clockIn($employee, now(), $hr, 'manual', [
            'latitude' => 12.9800000,
            'longitude' => 77.6000000,
            'accuracy_meters' => 20,
            'device_id' => 'device-1',
        ]);

        $this->assertSame('not_required', $record->clock_in_verification_status);
        $this->assertTrue((bool) data_get($record->clock_in_verification_metadata, 'metadata.wfh_exemption'));
    }

    public function test_selected_days_only_match_configured_weekdays(): void
    {
        [$organization, $hr, $employee] = $this->wfhScenario([], [
            'enabled' => true,
            'allowed_weekdays' => [1, 2, 3, 4, 5],
        ]);
        app(TenantContext::class)->set($organization);

        app(WfhPolicyService::class)->assign($employee, [
            'policy_type' => 'selected_days',
            'weekdays' => [1, 5], // Mon + Fri
            'effective_from' => '2026-08-01',
            'reason' => 'Hybrid',
        ], $hr);

        // Wednesday
        $this->assertFalse(app(WfhPolicyService::class)->isWfhDay($employee, '2026-08-12'));
        // Friday
        $this->assertTrue(app(WfhPolicyService::class)->isWfhDay($employee, '2026-08-14'));
        // Monday
        $this->assertTrue(app(WfhPolicyService::class)->isWfhDay($employee, '2026-08-17'));
    }

    public function test_daily_request_approval_flow_and_duplicate_prevention(): void
    {
        [$organization, $hr, $employee, $managerUser] = $this->wfhScenarioWithManager([], [
            'enabled' => true,
            'requires_approval' => true,
            'requires_hr_approval' => false,
            'allowed_weekdays' => [1, 2, 3, 4, 5],
        ]);
        app(TenantContext::class)->set($organization);

        $request = app(WfhRequestService::class)->submit($employee, [
            'work_date' => '2026-08-13',
            'reason' => 'Home delivery',
        ], $employee->user);

        $this->assertSame('pending', $request->status);
        $this->assertFalse(app(WfhPolicyService::class)->isWfhDay($employee, '2026-08-13'));

        try {
            app(WfhRequestService::class)->submit($employee, [
                'work_date' => '2026-08-13',
                'reason' => 'Duplicate',
            ], $employee->user);
            $this->fail('Expected duplicate WFH request to fail.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('work_date', $e->errors());
        }

        app(WfhRequestService::class)->approve($request, $managerUser, 'OK');
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertTrue(app(WfhPolicyService::class)->isWfhDay($employee, '2026-08-13'));
    }

    public function test_daily_request_takes_precedence_over_selected_days_and_permanent_blocks_request(): void
    {
        [$organization, $hr, $employee] = $this->wfhScenario([], [
            'enabled' => true,
            'requires_approval' => false,
            'allowed_weekdays' => [1, 2, 3, 4, 5],
        ]);
        app(TenantContext::class)->set($organization);

        app(WfhPolicyService::class)->assign($employee, [
            'policy_type' => 'selected_days',
            'weekdays' => [3],
            'effective_from' => '2026-08-01',
        ], $hr);

        $this->assertSame('selected_days', app(WfhPolicyService::class)->resolveForDate($employee, '2026-08-12')['policy_type']);

        WfhRequest::factory()->approved()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'work_date' => '2026-08-12',
        ]);

        $this->assertSame('daily', app(WfhPolicyService::class)->resolveForDate($employee, '2026-08-12')['policy_type']);

        app(WfhPolicyService::class)->assign($employee, [
            'policy_type' => 'permanent',
            'effective_from' => '2026-08-15',
            'reason' => 'Full remote',
        ], $hr);

        try {
            app(WfhRequestService::class)->submit($employee, [
                'work_date' => '2026-08-17',
                'reason' => 'Should fail',
            ], $hr);
            $this->fail('Expected permanent WFH to block daily request.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('work_date', $e->errors());
        }
    }

    public function test_cancellation_before_cutoff_clears_wfh_day(): void
    {
        [$organization, $hr, $employee] = $this->wfhScenario([], [
            'enabled' => true,
            'requires_approval' => false,
            'cancellation_cutoff_days' => 0,
            'allowed_weekdays' => [1, 2, 3, 4, 5],
        ]);
        app(TenantContext::class)->set($organization);

        $request = app(WfhRequestService::class)->submit($employee, [
            'work_date' => '2026-08-14',
            'reason' => 'Cancel me',
        ], $hr);

        $this->assertSame('approved', $request->status);
        app(WfhRequestService::class)->cancel($request, $hr, 'Changed plans');
        $this->assertSame('cancelled', $request->fresh()->status);
        $this->assertFalse(app(WfhPolicyService::class)->isWfhDay($employee, '2026-08-14'));
    }

    public function test_geofence_still_enforced_when_not_on_wfh(): void
    {
        [$organization, $hr, $employee] = $this->wfhScenario([
            'attendance_verification_mode' => 'geofence',
        ], [
            'enabled' => true,
            'bypass_geofence' => true,
        ]);
        app(TenantContext::class)->set($organization);

        AttendanceGeofence::factory()->create([
            'organization_id' => $organization->id,
            'latitude' => 12.9716000,
            'longitude' => 77.5946000,
            'radius_meters' => 100,
        ]);

        try {
            app(AttendanceService::class)->clockIn($employee, now(), $hr, 'mobile', [
                'latitude' => 12.9800000,
                'longitude' => 77.6000000,
                'accuracy_meters' => 20,
                'device_id' => 'device-1',
            ]);
            $this->fail('Expected outside geofence to fail without WFH.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('verification', $e->errors());
        }

        $this->assertSame(0, AttendanceRecord::query()->count());
    }

    public function test_overlapping_active_assignments_are_rejected(): void
    {
        [$organization, $hr, $employee] = $this->wfhScenario([], [
            'enabled' => true,
        ]);
        app(TenantContext::class)->set($organization);

        app(WfhPolicyService::class)->assign($employee, [
            'policy_type' => 'permanent',
            'effective_from' => '2026-08-01',
        ], $hr);

        try {
            app(WfhPolicyService::class)->assign($employee, [
                'policy_type' => 'permanent',
                'effective_from' => '2026-08-10',
            ], $hr);
            $this->fail('Expected overlapping assignment to fail.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('effective_from', $e->errors());
        }

        $this->assertSame(1, EmployeeWfhAssignment::query()->count());
    }

    public function test_permanent_takes_precedence_over_selected_days_and_outside_effective_dates_are_ignored(): void
    {
        [$organization, $hr, $employee] = $this->wfhScenario([], [
            'enabled' => true,
            'allowed_weekdays' => [1, 2, 3, 4, 5],
        ]);
        app(TenantContext::class)->set($organization);

        app(WfhPolicyService::class)->assign($employee, [
            'policy_type' => 'selected_days',
            'weekdays' => [3],
            'effective_from' => '2026-08-01',
        ], $hr);

        app(WfhPolicyService::class)->assign($employee, [
            'policy_type' => 'permanent',
            'effective_from' => '2026-08-01',
            'effective_to' => '2026-08-12',
        ], $hr);

        $this->assertSame('permanent', app(WfhPolicyService::class)->resolveForDate($employee, '2026-08-12')['policy_type']);
        // Permanent ended; selected_days still matches Wednesday.
        $this->assertSame('selected_days', app(WfhPolicyService::class)->resolveForDate($employee, '2026-08-19')['policy_type']);
        // Outside selected_days weekday after permanent ended.
        $this->assertFalse(app(WfhPolicyService::class)->isWfhDay($employee, '2026-08-20'));
    }

    public function test_wfh_exemption_writes_verification_audit_and_record_gps_still_requires_coordinates(): void
    {
        [$organization, $hr, $employee] = $this->wfhScenario([
            'attendance_verification_mode' => 'geofence',
        ], [
            'enabled' => true,
            'bypass_geofence' => true,
            'record_gps_when_wfh' => true,
        ]);
        app(TenantContext::class)->set($organization);

        AttendanceGeofence::factory()->create([
            'organization_id' => $organization->id,
            'latitude' => 12.9716000,
            'longitude' => 77.5946000,
            'radius_meters' => 100,
        ]);

        app(WfhPolicyService::class)->assign($employee, [
            'policy_type' => 'permanent',
            'effective_from' => '2026-08-01',
        ], $hr);

        try {
            app(AttendanceService::class)->clockIn($employee, now(), $hr, 'mobile', [
                'device_id' => 'device-1',
            ]);
            $this->fail('Expected GPS coordinates when record_gps_when_wfh is enabled.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('verification', $e->errors());
        }

        $record = app(AttendanceService::class)->clockIn($employee, now(), $hr, 'mobile', [
            'latitude' => 12.9800000,
            'longitude' => 77.6000000,
            'accuracy_meters' => 20,
            'device_id' => 'device-1',
        ]);

        $this->assertTrue((bool) data_get($record->clock_in_verification_metadata, 'metadata.wfh_exemption'));
        $this->assertTrue((bool) data_get($record->clock_in_verification_metadata, 'metadata.geofence_skipped'));
        $this->assertDatabaseHas('attendance_verification_audits', [
            'employee_id' => $employee->id,
            'event' => 'clock_in',
            'attendance_record_id' => $record->id,
        ]);
        $this->assertGreaterThan(0, AttendanceVerificationAudit::query()->where('employee_id', $employee->id)->count());
    }

    public function test_wfh_does_not_bypass_biometric_verification(): void
    {
        [$organization, $hr, $employee] = $this->wfhScenario([
            'attendance_verification_mode' => 'gps_and_biometric',
        ], [
            'enabled' => true,
            'bypass_geofence' => true,
            'record_gps_when_wfh' => false,
        ]);
        app(TenantContext::class)->set($organization);

        app(WfhPolicyService::class)->assign($employee, [
            'policy_type' => 'permanent',
            'effective_from' => '2026-08-01',
        ], $hr);

        try {
            app(AttendanceService::class)->clockIn($employee, now(), $hr, 'mobile', [
                'latitude' => 12.9800000,
                'longitude' => 77.6000000,
                'accuracy_meters' => 20,
                'device_id' => 'device-1',
            ]);
            $this->fail('Expected biometric requirement to remain enforced on WFH.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('verification', $e->errors());
        }

        $record = app(AttendanceService::class)->clockIn($employee, now(), $hr, 'mobile', [
            'latitude' => 12.9800000,
            'longitude' => 77.6000000,
            'accuracy_meters' => 20,
            'device_id' => 'device-1',
            'biometric_verified' => true,
            'biometric_reference' => 'bio-wfh-1',
            'biometric_provider' => 'device',
        ]);

        $this->assertSame('verified', $record->clock_in_verification_status);
        $this->assertNotNull(data_get($record->clock_in_verification_metadata, 'metadata.biometric'));
    }

    public function test_unauthorized_user_cannot_manage_or_approve_wfh_http(): void
    {
        [$organization, $hr, $employee, $managerUser] = $this->wfhScenarioWithManager([], [
            'enabled' => true,
            'requires_approval' => true,
            'requires_hr_approval' => false,
        ]);
        $session = ['current_organization_id' => $organization->id];

        $outsider = User::factory()->create();
        $organization->addMember($outsider, 'employee');

        $this->actingAs($outsider)->withSession($session)
            ->get(route('hrms.wfh.assignments.index'))
            ->assertForbidden();

        $this->actingAs($outsider)->withSession($session)
            ->post(route('hrms.wfh.assignments.store'), [
                'employee_id' => $employee->id,
                'policy_type' => 'permanent',
                'effective_from' => '2026-08-01',
            ])->assertForbidden();

        $request = app(WfhRequestService::class)->submit($employee, [
            'work_date' => '2026-08-13',
            'reason' => 'Need approval',
        ], $employee->user);

        $this->actingAs($outsider)->withSession($session)
            ->post(route('hrms.wfh.requests.approve', $request), [
                'comments' => 'Nope',
            ])->assertForbidden();

        $this->actingAs($managerUser)->withSession($session)
            ->get(route('hrms.wfh.requests.approval-queue'))
            ->assertOk();

        $this->actingAs($hr)->withSession($session)
            ->get(route('hrms.wfh.assignments.index'))
            ->assertOk();
    }

    public function test_cross_tenant_wfh_assignment_is_isolated(): void
    {
        [$orgA, $hrA, $employeeA] = $this->wfhScenario([], ['enabled' => true]);
        [$orgB, $hrB] = $this->wfhScenario([], ['enabled' => true]);

        app(TenantContext::class)->set($orgA);
        $assignment = app(WfhPolicyService::class)->assign($employeeA, [
            'policy_type' => 'permanent',
            'effective_from' => '2026-08-01',
            'reason' => 'TENANT-A-ONLY-WFH-MARKER',
        ], $hrA);

        app(TenantContext::class)->set($orgB);
        $this->assertNull(EmployeeWfhAssignment::query()->find($assignment->id));

        $this->actingAs($hrB)->withSession(['current_organization_id' => $orgB->id])
            ->get(route('hrms.wfh.assignments.index'))
            ->assertOk()
            ->assertDontSee('TENANT-A-ONLY-WFH-MARKER');
    }

    public function test_approved_leave_blocks_wfh_submit_and_suppresses_wfh_resolution(): void
    {
        [$organization, $hr, $employee] = $this->wfhScenario([], [
            'enabled' => true,
            'requires_approval' => false,
            'allowed_weekdays' => [1, 2, 3, 4, 5],
        ]);
        app(TenantContext::class)->set($organization);

        $leaveType = LeaveType::factory()->create(['organization_id' => $organization->id]);
        LeaveApplication::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'approved',
            'start_date' => '2026-08-13',
            'end_date' => '2026-08-13',
            'days' => 1,
        ]);

        try {
            app(WfhRequestService::class)->submit($employee, [
                'work_date' => '2026-08-13',
                'reason' => 'Conflict',
            ], $hr);
            $this->fail('Expected leave overlap to block WFH submit.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('work_date', $e->errors());
        }

        app(WfhPolicyService::class)->assign($employee, [
            'policy_type' => 'permanent',
            'effective_from' => '2026-08-01',
        ], $hr);

        $resolution = app(WfhPolicyService::class)->resolveForDate($employee, '2026-08-13');
        $this->assertFalse($resolution['is_wfh']);
        $this->assertTrue($resolution['suppressed_by_leave']);
    }

    public function test_employee_can_cancel_approved_wfh_via_ess_route(): void
    {
        [$organization, $hr, $employee] = $this->wfhScenario([], [
            'enabled' => true,
            'requires_approval' => false,
            'cancellation_cutoff_days' => 0,
            'allowed_weekdays' => [1, 2, 3, 4, 5],
        ]);
        $session = ['current_organization_id' => $organization->id];

        $request = app(WfhRequestService::class)->submit($employee, [
            'work_date' => '2026-08-14',
            'reason' => 'Cancel via ESS',
        ], $hr);
        $this->assertSame('approved', $request->status);

        $this->actingAs($employee->user)->withSession($session)
            ->post(route('ess.wfh.cancel', $request), ['remarks' => 'Plans changed'])
            ->assertRedirect(route('ess.wfh.index'));

        $this->assertSame('cancelled', $request->fresh()->status);
        $this->assertFalse(app(WfhPolicyService::class)->isWfhDay($employee, '2026-08-14'));
    }

    public function test_multi_day_wfh_request_covers_range_and_blocks_conflicts(): void
    {
        [$organization, $hr, $employee] = $this->wfhScenario([], [
            'enabled' => true,
            'requires_approval' => false,
            'allowed_weekdays' => [1, 2, 3, 4, 5],
        ]);
        app(TenantContext::class)->set($organization);

        $request = app(WfhRequestService::class)->submit($employee, [
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-14',
            'reason' => 'Multi-day',
        ], $hr);

        $this->assertSame('approved', $request->status);
        $this->assertTrue($request->isMultiDay());
        $this->assertTrue(app(WfhPolicyService::class)->isWfhDay($employee, '2026-08-12'));
        $this->assertTrue(app(WfhPolicyService::class)->isWfhDay($employee, '2026-08-13'));
        $this->assertTrue(app(WfhPolicyService::class)->isWfhDay($employee, '2026-08-14'));

        try {
            app(WfhRequestService::class)->submit($employee, [
                'work_date' => '2026-08-13',
                'reason' => 'Overlap',
            ], $hr);
            $this->fail('Expected overlapping multi-day conflict.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('work_date', $e->errors());
        }

        // Single-day still works outside the range.
        $single = app(WfhRequestService::class)->submit($employee, [
            'work_date' => '2026-08-17',
            'reason' => 'Single',
        ], $hr);
        $this->assertFalse($single->isMultiDay());
        $this->assertTrue(app(WfhPolicyService::class)->isWfhDay($employee, '2026-08-17'));
    }

    public function test_organization_transfer_ends_wfh_assignments_and_cancels_requests(): void
    {
        [$orgA, $hrA, $employee] = $this->wfhScenario([], [
            'enabled' => true,
            'requires_approval' => true,
            'requires_hr_approval' => false,
            'allowed_weekdays' => [1, 2, 3, 4, 5],
        ]);
        [$orgB] = $this->wfhScenario([], ['enabled' => true]);
        app(TenantContext::class)->set($orgA);

        // Pending request first (permanent assignment would block later daily submits).
        $request = app(WfhRequestService::class)->submit($employee, [
            'work_date' => '2026-08-17',
            'reason' => 'Future WFH',
        ], $hrA);
        $this->assertSame('pending', $request->status);

        $assignment = app(WfhPolicyService::class)->assign($employee, [
            'policy_type' => 'permanent',
            'effective_from' => '2026-08-01',
        ], $hrA);

        // Composite FKs prevent raw employee.organization_id updates; the transfer
        // handler is the supported cleanup path invoked by EmployeeService.
        $result = app(WfhPolicyService::class)->handleEmployeeOrganizationTransfer(
            $employee,
            (int) $orgA->id,
            $hrA,
        );

        $this->assertSame(1, $result['ended_assignments']);
        $this->assertSame(1, $result['cancelled_requests']);
        $this->assertFalse($assignment->fresh()->is_active);
        $this->assertSame('cancelled', $request->fresh()->status);

        // Simulate post-transfer membership in the destination org for resolution checks.
        $employee->setAttribute('organization_id', $orgB->id);
        app(TenantContext::class)->set($orgB);
        $this->assertFalse(app(WfhPolicyService::class)->isWfhDay($employee, '2026-08-12'));
        $this->assertFalse(app(WfhPolicyService::class)->isWfhDay($employee, '2026-08-17'));
    }

    public function test_branch_change_keeps_active_wfh_assignment(): void
    {
        [$organization, $hr, $employee] = $this->wfhScenario([], [
            'enabled' => true,
        ]);
        app(TenantContext::class)->set($organization);

        $fromBranch = \App\Models\Branch::factory()->create(['organization_id' => $organization->id]);
        $toBranch = \App\Models\Branch::factory()->create(['organization_id' => $organization->id]);
        $employee->update(['branch_id' => $fromBranch->id]);

        app(WfhPolicyService::class)->assign($employee, [
            'policy_type' => 'permanent',
            'effective_from' => '2026-08-01',
        ], $hr);

        app(EmployeeService::class)->updateEmployee($employee, [
            'branch_id' => $toBranch->id,
        ], $hr);

        $this->assertTrue(app(WfhPolicyService::class)->isWfhDay($employee->fresh(), '2026-08-12'));
        $this->assertSame($toBranch->id, $employee->fresh()->branch_id);
    }

    /**
     * @param  array<string, mixed>  $attendanceRules
     * @param  array<string, mixed>  $wfhPolicies
     * @return array{0: Organization, 1: User, 2: Employee}
     */
    private function wfhScenario(array $attendanceRules = [], array $wfhPolicies = []): array
    {
        $organization = Organization::factory()->create([
            'plan' => 'enterprise',
            'settings' => [
                'attendance_rules' => array_merge([
                    'attendance_verification_mode' => 'none',
                    'max_accuracy_meters' => 100,
                    'require_device_id' => false,
                ], $attendanceRules),
                'wfh_policies' => array_merge([
                    'enabled' => true,
                    'default_policy_type' => 'daily',
                    'requires_approval' => true,
                    'requires_hr_approval' => false,
                    'bypass_geofence' => true,
                    'record_gps_when_wfh' => false,
                    'allowed_weekdays' => [1, 2, 3, 4, 5],
                    'cancellation_cutoff_days' => 0,
                ], $wfhPolicies),
            ],
        ]);
        $hr = User::factory()->create();
        $organization->addMember($hr, 'hr');

        $employeeUser = User::factory()->create();
        $organization->addMember($employeeUser, 'employee');

        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $employeeUser->id,
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

        return [$organization, $hr, $employee->fresh(['user'])];
    }

    /**
     * @param  array<string, mixed>  $attendanceRules
     * @param  array<string, mixed>  $wfhPolicies
     * @return array{0: Organization, 1: User, 2: Employee, 3: User}
     */
    private function wfhScenarioWithManager(array $attendanceRules = [], array $wfhPolicies = []): array
    {
        [$organization, $hr, $employee] = $this->wfhScenario($attendanceRules, $wfhPolicies);

        $managerUser = User::factory()->create();
        $organization->addMember($managerUser, 'manager');
        $manager = Employee::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $managerUser->id,
            'status' => 'active',
        ]);
        $employee->update(['reporting_manager_id' => $manager->id]);

        return [$organization, $hr, $employee->fresh(['user', 'reportingManager']), $managerUser];
    }
}
