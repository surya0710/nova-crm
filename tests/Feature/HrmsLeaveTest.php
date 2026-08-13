<?php

namespace Tests\Feature;

use App\Events\LeaveApproved;
use App\Events\LeaveBalanceAdjusted;
use App\Events\LeaveCancelled;
use App\Events\LeaveRejected;
use App\Events\LeaveSubmitted;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\User;
use App\Services\Hrms\AttendanceService;
use App\Services\Hrms\LeaveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class HrmsLeaveTest extends TestCase
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

    public function test_leave_type_crud_and_audit(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.leave-types.store'), [
            'name' => 'Maternity Leave',
            'code' => 'ML',
            'allocation_days' => 90,
            'allow_half_day' => false,
            'requires_hr_approval' => true,
        ])->assertRedirect(route('hrms.leave-types.index'));

        $this->assertDatabaseHas('leave_types', ['code' => 'ML', 'organization_id' => $organization->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'leave_type_created']);

        $leaveType = LeaveType::query()->where('code', 'ML')->firstOrFail();

        $this->actingAs($hr)->withSession($session)->put(route('hrms.leave-types.update', $leaveType), [
            'name' => 'Maternity Leave Updated',
            'code' => 'ML',
            'allocation_days' => 90,
        ])->assertRedirect(route('hrms.leave-types.index'));

        $this->assertDatabaseHas('audit_logs', ['event' => 'leave_type_updated']);

        $this->actingAs($hr)->withSession($session)->delete(route('hrms.leave-types.destroy', $leaveType))
            ->assertRedirect(route('hrms.leave-types.index'));

        $this->assertSoftDeleted('leave_types', ['id' => $leaveType->id]);
    }

    public function test_holiday_crud(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.holidays.store'), [
            'name' => 'Independence Day',
            'holiday_date' => '2026-08-15',
            'is_recurring' => true,
        ])->assertRedirect(route('hrms.holidays.index'));

        $this->assertDatabaseHas('holidays', [
            'name' => 'Independence Day',
            'organization_id' => $organization->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'holiday_created']);

        $holiday = Holiday::query()->firstOrFail();
        $this->actingAs($hr)->withSession($session)->delete(route('hrms.holidays.destroy', $holiday))
            ->assertRedirect(route('hrms.holidays.index'));
    }

    public function test_balance_allocation_creates_ledger_entry(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id, 'status' => 'active']);
        $leaveType = LeaveType::factory()->create(['organization_id' => $organization->id]);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.leave-balances.allocate'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'days' => 12,
        ])->assertRedirect(route('hrms.leave-balances.index', ['year' => 2026]));

        $balance = LeaveBalance::query()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('12.00', $balance->balance);
        $this->assertDatabaseHas('leave_balance_transactions', [
            'leave_balance_id' => $balance->id,
            'transaction_type' => 'allocation',
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'leave_balance_allocated']);
    }

    public function test_manual_balance_adjustment_emits_workflow_event(): void
    {
        Event::fake([LeaveBalanceAdjusted::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $leaveType = LeaveType::factory()->create(['organization_id' => $organization->id]);
        $session = ['current_organization_id' => $organization->id];

        app(LeaveService::class)->allocateBalance($employee, $leaveType, 2026, 10, $hr);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.leave-balances.adjust'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'quantity' => 2,
            'remarks' => 'Bonus days',
        ])->assertRedirect(route('hrms.leave-balances.index', ['year' => 2026]));

        $balance = LeaveBalance::query()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('12.00', $balance->balance);
        $this->assertDatabaseHas('leave_balance_transactions', ['transaction_type' => 'manual_adjustment']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'leave_balance_adjusted']);
        Event::assertDispatched(LeaveBalanceAdjusted::class);
    }

    public function test_leave_apply_and_manager_approval_flow(): void
    {
        Event::fake([LeaveSubmitted::class, LeaveApproved::class]);

        [$organization, $hr, $manager, $managerEmployee, $employee, $leaveType] = $this->leaveScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.leave-applications.store'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-07-27',
            'end_date' => '2026-07-29',
            'reason' => 'Family event',
        ])->assertRedirect(route('hrms.leave-applications.index'));

        $application = LeaveApplication::query()->firstOrFail();
        $this->assertSame('pending', $application->status);
        $this->assertSame('3.00', $application->days);
        Event::assertDispatched(LeaveSubmitted::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'leave_applied']);
        $this->assertDatabaseHas('leave_balance_transactions', ['transaction_type' => 'leave_submitted']);

        $this->actingAs($manager)->withSession($session)->post(route('hrms.leave-applications.approve', $application))
            ->assertRedirect(route('hrms.leave-applications.show', $application));

        $application->refresh();
        $this->assertSame('approved', $application->status);
        Event::assertDispatched(LeaveApproved::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'leave_approved']);
        $this->assertDatabaseHas('leave_balance_transactions', ['transaction_type' => 'leave_approved']);

        $balance = LeaveBalance::query()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('3.00', $balance->used);
        $this->assertSame('9.00', $balance->balance);
    }

    public function test_half_day_leave(): void
    {
        [$organization, $hr, , , $employee, $leaveType] = $this->leaveScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.leave-applications.store'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-07-27',
            'end_date' => '2026-07-27',
            'is_half_day' => true,
            'half_day_period' => 'first_half',
        ])->assertRedirect(route('hrms.leave-applications.index'));

        $application = LeaveApplication::query()->firstOrFail();
        $this->assertSame('0.50', $application->days);
        $this->assertTrue($application->is_half_day);
    }

    public function test_overlapping_leave_is_rejected(): void
    {
        [$organization, $hr, $manager, , $employee, $leaveType] = $this->leaveScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.leave-applications.store'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-07-27',
            'end_date' => '2026-07-29',
        ]);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.leave-applications.store'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-07-28',
            'end_date' => '2026-07-30',
        ])->assertSessionHasErrors('start_date');
    }

    public function test_hr_approval_chain(): void
    {
        [$organization, $hr, $manager, , $employee] = $this->leaveScenario();
        $leaveType = LeaveType::factory()->create([
            'organization_id' => $organization->id,
            'requires_hr_approval' => true,
            'allow_half_day' => true,
        ]);
        app(LeaveService::class)->allocateBalance($employee, $leaveType, 2026, 10, $hr);

        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.leave-applications.store'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-05',
        ]);

        $application = LeaveApplication::query()->firstOrFail();
        $this->assertCount(2, $application->approvalSteps);

        $this->actingAs($manager)->withSession($session)->post(route('hrms.leave-applications.approve', $application));
        $this->assertSame('pending', $application->fresh()->status);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.leave-applications.approve', $application));
        $this->assertSame('approved', $application->fresh()->status);
    }

    public function test_rejection_restores_pending_balance(): void
    {
        Event::fake([LeaveRejected::class]);

        [$organization, $hr, $manager, , $employee, $leaveType] = $this->leaveScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.leave-applications.store'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-07-27',
            'end_date' => '2026-07-27',
        ]);

        $application = LeaveApplication::query()->firstOrFail();
        $balance = LeaveBalance::query()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('1.00', $balance->pending);

        $this->actingAs($manager)->withSession($session)->post(route('hrms.leave-applications.reject', $application), [
            'remarks' => 'Not approved',
        ]);

        $balance->refresh();
        $this->assertSame('0.00', $balance->pending);
        $this->assertSame('12.00', $balance->balance);
        $this->assertSame('rejected', $application->fresh()->status);
        Event::assertDispatched(LeaveRejected::class);
    }

    public function test_cancellation_restores_used_balance(): void
    {
        Event::fake([LeaveCancelled::class]);

        [$organization, $hr, $manager, , $employee, $leaveType] = $this->leaveScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.leave-applications.store'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-07-27',
            'end_date' => '2026-07-27',
        ]);

        $application = LeaveApplication::query()->firstOrFail();
        $this->actingAs($manager)->withSession($session)->post(route('hrms.leave-applications.approve', $application));

        $this->actingAs($hr)->withSession($session)->post(route('hrms.leave-applications.cancel', $application), [
            'remarks' => 'Plans changed',
        ]);

        $balance = LeaveBalance::query()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('0.00', $balance->used);
        $this->assertSame('12.00', $balance->balance);
        $this->assertDatabaseHas('leave_balance_transactions', ['transaction_type' => 'leave_cancelled']);
        Event::assertDispatched(LeaveCancelled::class);
    }

    public function test_attendance_reads_approved_leave_without_modifying_balance(): void
    {
        [$organization, $hr, $manager, , $employee, $leaveType] = $this->leaveScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.leave-applications.store'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-07-27',
            'end_date' => '2026-07-27',
        ]);

        $application = LeaveApplication::query()->firstOrFail();
        $this->actingAs($manager)->withSession($session)->post(route('hrms.leave-applications.approve', $application));

        $balanceBefore = LeaveBalance::query()->where('employee_id', $employee->id)->firstOrFail()->balance;

        $attendanceService = app(AttendanceService::class);
        $this->assertTrue($attendanceService->isEmployeeOnLeave($employee, Carbon::parse('2026-07-27')));
        $this->assertFalse($attendanceService->isEmployeeOnLeave($employee, Carbon::parse('2026-07-20')));

        $balanceAfter = LeaveBalance::query()->where('employee_id', $employee->id)->firstOrFail()->balance;
        $this->assertSame($balanceBefore, $balanceAfter);
    }

    public function test_cross_organization_leave_access_is_forbidden(): void
    {
        [$orgA, $hrA] = $this->organizationWithHrUser();
        [$orgB] = $this->organizationWithHrUser();
        $employeeB = Employee::factory()->create(['organization_id' => $orgB->id]);
        $leaveTypeB = LeaveType::factory()->create(['organization_id' => $orgB->id]);
        $applicationB = LeaveApplication::factory()->create([
            'organization_id' => $orgB->id,
            'employee_id' => $employeeB->id,
            'leave_type_id' => $leaveTypeB->id,
        ]);

        $this->actingAs($hrA)->withSession(['current_organization_id' => $orgA->id])
            ->get(route('hrms.leave-applications.show', $applicationB))
            ->assertForbidden();
    }

    public function test_manager_can_approve_but_not_manage_leave_types(): void
    {
        [$organization, $hr, $manager] = $this->leaveScenario(returnEmployeeOnly: true);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($manager)->withSession($session)->get(route('hrms.leave-applications.index'))->assertOk();
        $this->actingAs($manager)->withSession($session)->get(route('hrms.leave-applications.approval-queue'))->assertOk();
        $this->actingAs($manager)->withSession($session)->post(route('hrms.leave-types.store'), [
            'name' => 'Blocked',
            'code' => 'BLK',
        ])->assertForbidden();
    }

    public function test_unauthorized_user_cannot_access_leave(): void
    {
        [$organization] = $this->organizationWithHrUser();
        $user = User::factory()->create();
        $organization->addMember($user, 'employee');

        $this->actingAs($user)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.leave-applications.index'))
            ->assertForbidden();
    }

    /** @return array{0: Organization, 1: User} */
    private function organizationWithHrUser(): array
    {
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        return [$organization, $user];
    }

    /**
     * @return array{0: Organization, 1: User, 2: User, 3: Employee, 4: Employee, 5: LeaveType}|array{0: Organization, 1: User, 2: User}
     */
    private function leaveScenario(bool $organizationOnly = false, bool $returnEmployeeOnly = false): array
    {
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $hr = User::factory()->create();
        $organization->addMember($hr, 'hr');

        $manager = User::factory()->create();
        $organization->addMember($manager, 'manager');

        if ($returnEmployeeOnly) {
            return [$organization, $hr, $manager];
        }

        $managerEmployee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $manager->id,
            'status' => 'active',
        ]);

        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'reporting_manager_id' => $managerEmployee->id,
            'status' => 'active',
            'joining_date' => '2026-01-01',
        ]);

        $leaveType = LeaveType::factory()->create([
            'organization_id' => $organization->id,
            'requires_hr_approval' => false,
            'allow_half_day' => true,
            'max_consecutive_days' => 10,
        ]);

        app(LeaveService::class)->allocateBalance($employee, $leaveType, 2026, 12, $hr);

        if ($organizationOnly) {
            return [$organization, $hr];
        }

        return [$organization, $hr, $manager, $managerEmployee, $employee, $leaveType];
    }
}
