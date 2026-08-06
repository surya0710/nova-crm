<?php

namespace Tests\Feature;

use App\Models\AttendanceOvertimeEntry;
use App\Models\AttendanceOvertimeRule;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\Hrms\OvertimeCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceOvertimeHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_create_overtime_rule(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];

        $response = $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.overtime.rules.store'), [
            'name' => 'Daily OT',
            'code' => 'OT-DAILY',
            'rule_type' => AttendanceOvertimeRule::TYPE_DAILY,
            'minimum_minutes' => 30,
            'maximum_minutes' => 180,
            'round_off_minutes' => 15,
            'multiplier' => 1.5,
            'requires_approval' => 1,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('hrms.attendance.overtime.rules'));
        $response->assertSessionHas('status', __('attendance.overtime.rule_created'));

        $this->assertDatabaseHas('attendance_overtime_rules', [
            'organization_id' => $organization->id,
            'code' => 'OT-DAILY',
            'rule_type' => AttendanceOvertimeRule::TYPE_DAILY,
            'requires_approval' => true,
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'attendance_overtime_rule_created']);
    }

    public function test_rule_creation_validation_failures(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.attendance.overtime.rules.store'), [
            'name' => '',
            'rule_type' => 'invalid-type',
            'minimum_minutes' => -1,
        ])->assertSessionHasErrors(['name', 'rule_type', 'minimum_minutes']);
    }

    public function test_unauthorized_user_cannot_manage_rules(): void
    {
        [$organization] = $this->organizationWithHrUser();
        $employeeUser = User::factory()->create();
        $organization->addMember($employeeUser, 'employee');
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($employeeUser)->withSession($session)
            ->get(route('hrms.attendance.overtime.rules'))
            ->assertForbidden();

        $this->actingAs($employeeUser)->withSession($session)
            ->post(route('hrms.attendance.overtime.rules.store'), [
                'name' => 'Blocked',
                'rule_type' => AttendanceOvertimeRule::TYPE_DAILY,
            ])
            ->assertForbidden();
    }

    public function test_rule_update_activate_and_deactivate(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];

        $rule = AttendanceOvertimeRule::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Holiday OT',
            'code' => 'OT-HOL',
            'rule_type' => AttendanceOvertimeRule::TYPE_HOLIDAY,
            'minimum_minutes' => 0,
            'is_active' => true,
            'requires_approval' => true,
        ]);

        $this->actingAs($hr)->withSession($session)->put(route('hrms.attendance.overtime.rules.update', $rule), [
            'name' => 'Holiday OT Updated',
            'code' => 'OT-HOL',
            'rule_type' => AttendanceOvertimeRule::TYPE_HOLIDAY,
            'minimum_minutes' => 45,
            'requires_approval' => 1,
            'is_active' => 1,
        ])->assertRedirect(route('hrms.attendance.overtime.rules'))
            ->assertSessionHas('status', __('attendance.overtime.rule_updated'));

        $this->assertDatabaseHas('audit_logs', ['event' => 'attendance_overtime_rule_updated']);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.attendance.overtime.rules.deactivate', $rule))
            ->assertRedirect(route('hrms.attendance.overtime.rules'))
            ->assertSessionHas('status', __('attendance.overtime.rule_deactivated'));

        $this->assertDatabaseHas('attendance_overtime_rules', [
            'id' => $rule->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'attendance_overtime_rule_deactivated']);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.attendance.overtime.rules.activate', $rule))
            ->assertRedirect(route('hrms.attendance.overtime.rules'))
            ->assertSessionHas('status', __('attendance.overtime.rule_activated'));

        $this->assertDatabaseHas('audit_logs', ['event' => 'attendance_overtime_rule_activated']);
    }

    public function test_entry_approval_and_rejection(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);

        $pending = $this->makeEntry($organization, $employee, AttendanceOvertimeEntry::STATUS_PENDING);
        $toReject = $this->makeEntry($organization, $employee, AttendanceOvertimeEntry::STATUS_PENDING, [
            'attendance_date' => '2026-07-21',
        ]);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.attendance.overtime.entries.approve', $pending), [
                'review_notes' => 'Looks good',
            ])
            ->assertRedirect(route('hrms.attendance.overtime.entries'))
            ->assertSessionHas('status', __('attendance.overtime.approved'));

        $this->assertDatabaseHas('attendance_overtime_entries', [
            'id' => $pending->id,
            'status' => AttendanceOvertimeEntry::STATUS_APPROVED,
            'review_notes' => 'Looks good',
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'overtime_entry_approved']);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.attendance.overtime.entries.reject', $toReject), [
                'review_notes' => 'Insufficient proof',
            ])
            ->assertRedirect(route('hrms.attendance.overtime.entries'))
            ->assertSessionHas('status', __('attendance.overtime.rejected'));

        $this->assertDatabaseHas('attendance_overtime_entries', [
            'id' => $toReject->id,
            'status' => AttendanceOvertimeEntry::STATUS_REJECTED,
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'overtime_entry_rejected']);
    }

    public function test_cross_organization_overtime_access_is_forbidden(): void
    {
        [$orgA, $hrA] = $this->organizationWithHrUser();
        [$orgB] = $this->organizationWithHrUser();

        $employeeB = Employee::factory()->create(['organization_id' => $orgB->id]);
        $ruleB = AttendanceOvertimeRule::query()->create([
            'organization_id' => $orgB->id,
            'name' => 'Org B Rule',
            'rule_type' => AttendanceOvertimeRule::TYPE_DAILY,
            'is_active' => true,
        ]);
        $entryB = $this->makeEntry($orgB, $employeeB, AttendanceOvertimeEntry::STATUS_PENDING, [
            'attendance_overtime_rule_id' => $ruleB->id,
        ]);

        $sessionA = ['current_organization_id' => $orgA->id];

        $editResponse = $this->actingAs($hrA)->withSession($sessionA)
            ->get(route('hrms.attendance.overtime.rules.edit', $ruleB));
        $this->assertTrue(in_array($editResponse->status(), [403, 404], true));

        $approveResponse = $this->actingAs($hrA)->withSession($sessionA)
            ->post(route('hrms.attendance.overtime.entries.approve', $entryB));
        $this->assertTrue(in_array($approveResponse->status(), [403, 404], true));
    }

    public function test_entries_listing_supports_filters_and_pagination(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();

        $branch = Branch::factory()->create(['organization_id' => $organization->id]);
        $department = Department::factory()->create(['organization_id' => $organization->id]);
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'first_name' => 'Asha',
            'last_name' => 'Verma',
        ]);
        $other = Employee::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Other',
        ]);

        $rule = AttendanceOvertimeRule::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Filter Rule',
            'rule_type' => AttendanceOvertimeRule::TYPE_DAILY,
            'is_active' => true,
        ]);

        $this->makeEntry($organization, $employee, AttendanceOvertimeEntry::STATUS_PENDING, [
            'attendance_overtime_rule_id' => $rule->id,
            'attendance_date' => '2026-07-20',
            'minutes' => 90,
        ]);
        $this->makeEntry($organization, $other, AttendanceOvertimeEntry::STATUS_APPROVED, [
            'attendance_date' => '2026-07-19',
            'minutes' => 30,
        ]);

        app(\App\Services\TenantContext::class)->set($organization);

        $request = \Illuminate\Http\Request::create('/hrms/attendance/overtime/entries', 'GET', [
            'status' => AttendanceOvertimeEntry::STATUS_PENDING,
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'rule_id' => $rule->id,
            'date_from' => '2026-07-20',
            'date_to' => '2026-07-20',
            'search' => 'Asha',
        ]);

        $entries = app(\App\Services\Hrms\AttendanceOvertimeListingService::class)->paginateEntries($request);

        $this->assertCount(1, $entries->items());
        $this->assertSame($employee->id, $entries->first()->employee_id);
        $this->assertTrue($entries instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator);
    }

    public function test_bulk_ready_service_approves_and_rejects_collections(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);

        $first = $this->makeEntry($organization, $employee, AttendanceOvertimeEntry::STATUS_PENDING, [
            'attendance_date' => '2026-07-20',
        ]);
        $second = $this->makeEntry($organization, $employee, AttendanceOvertimeEntry::STATUS_PENDING, [
            'attendance_date' => '2026-07-21',
        ]);
        $third = $this->makeEntry($organization, $employee, AttendanceOvertimeEntry::STATUS_PENDING, [
            'attendance_date' => '2026-07-22',
        ]);

        $service = app(OvertimeCalculationService::class);

        $service->approveEntries(collect([$first, $second]), ['review_notes' => 'Bulk OK'], $hr);
        $service->rejectEntries(collect([$third]), ['review_notes' => 'Bulk reject'], $hr);

        $this->assertSame(AttendanceOvertimeEntry::STATUS_APPROVED, $first->fresh()->status);
        $this->assertSame(AttendanceOvertimeEntry::STATUS_APPROVED, $second->fresh()->status);
        $this->assertSame(AttendanceOvertimeEntry::STATUS_REJECTED, $third->fresh()->status);
    }

    public function test_review_notes_max_length_validation(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $entry = $this->makeEntry($organization, $employee, AttendanceOvertimeEntry::STATUS_PENDING);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.attendance.overtime.entries.approve', $entry), [
                'review_notes' => str_repeat('x', 2001),
            ])
            ->assertSessionHasErrors('review_notes');
    }

    /** @return array{0: Organization, 1: User} */
    private function organizationWithHrUser(): array
    {
        $organization = Organization::factory()->create(['plan' => 'professional']);
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        return [$organization, $user];
    }

    /** @param  array<string, mixed>  $overrides */
    private function makeEntry(
        Organization $organization,
        Employee $employee,
        string $status,
        array $overrides = [],
    ): AttendanceOvertimeEntry {
        $record = AttendanceRecord::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'attendance_date' => $overrides['attendance_date'] ?? '2026-07-20',
            'overtime_minutes' => $overrides['minutes'] ?? 60,
            'status' => 'present',
        ]);

        return AttendanceOvertimeEntry::query()->create(array_merge([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'attendance_record_id' => $record->id,
            'attendance_date' => $overrides['attendance_date'] ?? '2026-07-20',
            'rule_type' => AttendanceOvertimeRule::TYPE_DAILY,
            'minutes' => 60,
            'status' => $status,
        ], $overrides));
    }
}
