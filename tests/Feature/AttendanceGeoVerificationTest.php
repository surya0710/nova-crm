<?php

namespace Tests\Feature;

use App\Models\AttendanceGeofence;
use App\Models\AttendanceRecord;
use App\Models\AttendanceVerificationAudit;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\HrmsShift;
use App\Models\Organization;
use App\Models\User;
use App\Services\Hrms\AttendanceService;
use App\Services\Hrms\GeofenceService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceGeoVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-10 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_none_mode_allows_clock_without_coordinates(): void
    {
        [$organization, $hr, $employee] = $this->attendanceScenario();
        app(TenantContext::class)->set($organization);

        $record = app(AttendanceService::class)->clockIn($employee, now(), $hr);

        $this->assertSame('not_required', $record->clock_in_verification_status);
        $this->assertDatabaseHas('attendance_verification_audits', [
            'employee_id' => $employee->id,
            'event' => 'clock_in',
            'verification_status' => 'not_required',
        ]);
    }

    public function test_geofence_mode_rejects_outside_and_accepts_inside(): void
    {
        [$organization, $hr, $employee] = $this->attendanceScenario([
            'attendance_verification_mode' => 'geofence',
            'max_accuracy_meters' => 100,
        ]);
        app(TenantContext::class)->set($organization);

        AttendanceGeofence::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'HQ',
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
            $this->fail('Expected outside geofence to fail.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('verification', $e->errors());
        }

        $this->assertDatabaseHas('attendance_verification_audits', [
            'employee_id' => $employee->id,
            'verification_status' => 'failed',
            'reason' => 'outside_geofence',
        ]);
        $this->assertSame(0, AttendanceRecord::query()->count());

        $record = app(AttendanceService::class)->clockIn($employee, now(), $hr, 'mobile', [
            'latitude' => 12.9716000,
            'longitude' => 77.5946000,
            'accuracy_meters' => 15,
            'device_id' => 'device-1',
        ]);

        $this->assertSame('verified', $record->clock_in_verification_status);
        $this->assertNotNull($record->clock_in_geofence_id);
        $this->assertSame(12.9716, round((float) $record->clock_in_latitude, 4));
    }

    public function test_gps_and_biometric_requires_both(): void
    {
        [$organization, $hr, $employee] = $this->attendanceScenario([
            'attendance_verification_mode' => 'gps_and_biometric',
        ]);
        app(TenantContext::class)->set($organization);

        try {
            app(AttendanceService::class)->clockIn($employee, now(), $hr, 'mobile', [
                'latitude' => 12.9716,
                'longitude' => 77.5946,
                'accuracy_meters' => 10,
            ]);
            $this->fail('Expected biometric requirement to fail.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('verification', $e->errors());
        }

        $record = app(AttendanceService::class)->clockIn($employee, now(), $hr, 'mobile', [
            'latitude' => 12.9716,
            'longitude' => 77.5946,
            'accuracy_meters' => 10,
            'device_id' => 'bio-device',
            'biometric_verified' => true,
            'biometric_reference' => 'bio-ref-1',
            'biometric_provider' => 'device',
        ]);

        $this->assertSame('verified', $record->clock_in_verification_status);
        $this->assertSame('bio-device', $record->clock_in_device_id);
    }

    public function test_clock_out_preserves_history_via_version_and_audit(): void
    {
        [$organization, $hr, $employee] = $this->attendanceScenario([
            'attendance_verification_mode' => 'gps',
        ]);
        app(TenantContext::class)->set($organization);

        $service = app(AttendanceService::class);
        $service->clockIn($employee, now(), $hr, 'mobile', [
            'latitude' => 12.9716,
            'longitude' => 77.5946,
            'accuracy_meters' => 12,
            'device_id' => 'device-a',
        ]);

        Carbon::setTestNow('2026-08-10 18:00:00');
        $record = $service->clockOut($employee, now(), $hr, [
            'latitude' => 12.9717,
            'longitude' => 77.5947,
            'accuracy_meters' => 18,
            'device_id' => 'device-a',
        ]);

        $this->assertSame(2, $record->version);
        $this->assertSame('verified', $record->clock_out_verification_status);
        $this->assertSame(2, AttendanceVerificationAudit::query()->where('employee_id', $employee->id)->count());
    }

    public function test_geofence_service_prefers_branch_scoped_fences(): void
    {
        [$organization, $hr, $employee] = $this->attendanceScenario();
        app(TenantContext::class)->set($organization);

        $branch = Branch::factory()->create([
            'organization_id' => $organization->id,
            'is_active' => true,
        ]);
        $employee->update(['branch_id' => $branch->id]);

        AttendanceGeofence::factory()->create([
            'organization_id' => $organization->id,
            'branch_id' => null,
            'name' => 'Org Wide',
            'latitude' => 12.0,
            'longitude' => 77.0,
            'radius_meters' => 50,
        ]);
        $branchFence = AttendanceGeofence::factory()->create([
            'organization_id' => $organization->id,
            'branch_id' => $branch->id,
            'name' => 'Branch Fence',
            'latitude' => 13.0,
            'longitude' => 78.0,
            'radius_meters' => 80,
        ]);

        $resolved = app(GeofenceService::class)->resolveApplicableGeofences($employee->fresh());
        $this->assertCount(1, $resolved);
        $this->assertSame($branchFence->id, $resolved->first()->id);
    }

    /**
     * @param  array<string, mixed>  $attendanceRules
     * @return array{0: Organization, 1: User, 2: Employee}
     */
    private function attendanceScenario(array $attendanceRules = []): array
    {
        $organization = Organization::factory()->create([
            'settings' => [
                'attendance_rules' => array_merge([
                    'attendance_verification_mode' => 'none',
                    'max_accuracy_meters' => 100,
                    'require_device_id' => false,
                ], $attendanceRules),
            ],
        ]);
        $hr = User::factory()->create();
        $organization->addMember($hr, 'hr');

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

        return [$organization, $hr, $employee];
    }
}
