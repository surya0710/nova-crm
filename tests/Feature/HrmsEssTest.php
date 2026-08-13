<?php

namespace Tests\Feature;

use App\Events\AnnouncementCreated;
use App\Events\EmployeeProfileUpdated;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\User;
use App\Services\Hrms\LeaveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class HrmsEssTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-21 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_employee_dashboard_requires_linked_employee(): void
    {
        [$organization, $user] = $this->employeeUser(withEmployee: false);

        $this->actingAs($user)->withSession(['current_organization_id' => $organization->id])
            ->get(route('ess.dashboard'))
            ->assertOk()
            ->assertSee('No employee record is linked to your account.');
    }

    public function test_employee_dashboard_displays_summary(): void
    {
        [$organization, $user, $employee] = $this->employeeUser();

        $this->actingAs($user)->withSession(['current_organization_id' => $organization->id])
            ->get(route('ess.dashboard'))
            ->assertOk()
            ->assertSee('My HR Dashboard')
            ->assertSee($employee->first_name);
    }

    public function test_employee_profile_update_and_audit(): void
    {
        Event::fake([EmployeeProfileUpdated::class]);

        [$organization, $user, $employee] = $this->employeeUser();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($user)->withSession($session)->put(route('ess.profile.update'), [
            'phone' => '9876543210',
            'mobile' => '9876543210',
            'personal_email' => 'personal@example.com',
            'city' => 'Mumbai',
        ])->assertRedirect(route('ess.profile'));

        $employee->refresh();
        $this->assertSame('9876543210', $employee->phone);
        $this->assertSame('Mumbai', $employee->city);
        $this->assertDatabaseHas('audit_logs', ['event' => 'employee_profile_updated']);
        Event::assertDispatched(EmployeeProfileUpdated::class);
    }

    public function test_employee_can_view_own_documents(): void
    {
        [$organization, $user, $employee] = $this->employeeUser();
        $other = Employee::factory()->create(['organization_id' => $organization->id]);
        $ownDoc = EmployeeDocument::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'title' => 'Own Passport',
        ]);
        EmployeeDocument::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $other->id,
            'title' => 'Other Doc',
        ]);

        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($user)->withSession($session)->get(route('ess.documents.index'))
            ->assertOk()
            ->assertSee('Own Passport')
            ->assertDontSee('Other Doc');

        $this->actingAs($user)->withSession($session)->get(route('ess.documents.show', $ownDoc))
            ->assertOk();
    }

    public function test_employee_attendance_self_service(): void
    {
        [$organization, $user, $employee] = $this->employeeUser();
        $session = ['current_organization_id' => $organization->id];

        // ESS attendance index is the calendar surface (Check In / Check Out).
        $this->actingAs($user)->withSession($session)->get(route('ess.attendance.index'))
            ->assertOk()
            ->assertSee('Check In');

        $this->actingAs($user)->withSession($session)->post(route('ess.attendance.clock-in'))
            ->assertRedirect(route('ess.attendance.index'));

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $employee->id,
        ]);
    }

    public function test_employee_leave_self_service(): void
    {
        [$organization, $user, $employee] = $this->employeeUser();
        $leaveType = LeaveType::factory()->create(['organization_id' => $organization->id]);
        $hr = User::factory()->create();
        $organization->addMember($hr, 'hr');
        app(LeaveService::class)->allocateBalance($employee, $leaveType, 2026, 5, $hr);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($user)->withSession($session)->get(route('ess.leave.index'))
            ->assertOk()
            ->assertSee('Apply Leave');

        $this->actingAs($user)->withSession($session)->post(route('ess.leave.store'), [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-07-28',
            'end_date' => '2026-07-28',
            'reason' => 'Personal',
        ])->assertRedirect(route('ess.leave.index'));

        $this->assertDatabaseHas('leave_applications', [
            'employee_id' => $employee->id,
            'status' => 'pending',
        ]);
    }

    public function test_manager_dashboard_shows_team_only_metrics(): void
    {
        [$organization, $managerUser, $managerEmployee, $teamMember] = $this->managerScenario();
        $session = ['current_organization_id' => $organization->id];

        LeaveApplication::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $teamMember->id,
            'leave_type_id' => LeaveType::factory()->create(['organization_id' => $organization->id])->id,
            'status' => 'pending',
            'start_date' => '2026-07-28',
            'end_date' => '2026-07-28',
            'days' => 1,
            'submitted_at' => now(),
        ]);

        $this->actingAs($managerUser)->withSession($session)->get(route('hrms.manager.dashboard'))
            ->assertOk()
            ->assertSee('Manager Dashboard')
            ->assertSee($teamMember->first_name);
    }

    public function test_hr_dashboard_and_announcement_crud(): void
    {
        Event::fake([AnnouncementCreated::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->get(route('hrms.dashboard'))
            ->assertOk()
            ->assertSee('HR Dashboard');

        $this->actingAs($hr)->withSession($session)->post(route('hrms.announcements.store'), [
            'title' => 'Company Holiday',
            'body' => 'Office closed next Friday.',
            'target_audience' => 'everyone',
            'start_date' => '2026-07-21',
            'end_date' => '2026-08-21',
            'is_active' => true,
        ])->assertRedirect(route('hrms.announcements.index'));

        $this->assertDatabaseHas('hrms_announcements', ['title' => 'Company Holiday']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'announcement_created']);
        Event::assertDispatched(AnnouncementCreated::class);
    }

    public function test_cross_tenant_ess_access_is_forbidden(): void
    {
        [$orgA, $userA] = $this->employeeUser();
        [$orgB] = $this->organizationWithHrUser();
        $employeeB = Employee::factory()->create(['organization_id' => $orgB->id]);
        $documentB = EmployeeDocument::factory()->create([
            'organization_id' => $orgB->id,
            'employee_id' => $employeeB->id,
        ]);

        $this->actingAs($userA)->withSession(['current_organization_id' => $orgA->id])
            ->get(route('ess.documents.show', $documentB))
            ->assertNotFound();
    }

    public function test_employee_cannot_access_hr_dashboard(): void
    {
        [$organization, $user] = $this->employeeUser();

        $this->actingAs($user)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.dashboard'))
            ->assertForbidden();
    }

    public function test_manager_cannot_manage_announcements(): void
    {
        [$organization, $managerUser] = $this->managerScenario(returnTeamMember: false);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($managerUser)->withSession($session)->get(route('hrms.announcements.index'))
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

    /** @return array{0: Organization, 1: User, 2: Employee}|array{0: Organization, 1: User} */
    private function employeeUser(bool $withEmployee = true): array
    {
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $user = User::factory()->create();
        $organization->addMember($user, 'employee');

        if (! $withEmployee) {
            return [$organization, $user];
        }

        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'status' => 'active',
            'joining_date' => '2026-01-01',
        ]);

        return [$organization, $user, $employee];
    }

    /** @return array{0: Organization, 1: User, 2: Employee, 3?: Employee} */
    private function managerScenario(bool $returnTeamMember = true): array
    {
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $managerUser = User::factory()->create();
        $organization->addMember($managerUser, 'manager');

        $managerEmployee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $managerUser->id,
            'status' => 'active',
        ]);

        if (! $returnTeamMember) {
            return [$organization, $managerUser, $managerEmployee];
        }

        $teamMember = Employee::factory()->create([
            'organization_id' => $organization->id,
            'reporting_manager_id' => $managerEmployee->id,
            'status' => 'active',
        ]);

        return [$organization, $managerUser, $managerEmployee, $teamMember];
    }
}
