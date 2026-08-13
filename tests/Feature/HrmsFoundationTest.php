<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Holiday;
use App\Models\HrmsShift;
use App\Models\HrmsTeam;
use App\Models\LeaveApplication;
use App\Models\LeaveApprovalStep;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use App\Services\Hrms\AttendanceService;
use App\Services\Hrms\BranchService;
use App\Services\Hrms\DepartmentService;
use App\Services\Hrms\DesignationService;
use App\Services\Hrms\EmployeeDocumentService;
use App\Services\Hrms\EmployeeService;
use App\Services\Hrms\EssContext;
use App\Services\Hrms\HrmsDashboardService;
use App\Services\Hrms\LeaveService;
use App\Services\Hrms\TeamService;
use App\Services\Navigation\NavigationService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HrmsFoundationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function expectedTables(): array
    {
        return [
            'hrms_branches',
            'hrms_departments',
            'hrms_designations',
            'hrms_teams',
            'employees',
            'employee_emergency_contacts',
            'employee_bank_accounts',
            'employee_identities',
            'employee_educations',
            'employee_experiences',
            'employee_documents',
            'employee_document_versions',
            'hrms_shifts',
            'employee_shift_assignments',
            'attendance_records',
            'attendance_corrections',
            'leave_types',
            'holidays',
            'leave_balances',
            'leave_applications',
            'leave_approval_steps',
            'hrms_announcements',
        ];
    }

    public function test_hrms_foundation_tables_exist(): void
    {
        foreach ($this->expectedTables() as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_hrms_permissions_are_seeded_and_granted_to_default_roles(): void
    {
        $organization = Organization::factory()->create();
        $hr = User::factory()->create();
        $employee = User::factory()->create();
        $sales = User::factory()->create();

        $organization->addMember($hr, 'hr');
        $organization->addMember($employee, 'employee');
        $organization->addMember($sales, 'sales-executive');

        foreach ([
            'hrms.view', 'hrms.create', 'hrms.update', 'hrms.manage', 'hrms.documents.manage',
            'attendance.view', 'attendance.manage', 'attendance.correct',
            'leave.view', 'leave.manage', 'leave.approve',
            'ess.access',
        ] as $slug) {
            $this->assertTrue(
                Permission::query()->where('slug', $slug)->exists(),
                "Missing permission: {$slug}"
            );
        }

        $this->assertTrue($hr->hasPermission('hrms.manage', $organization));
        $this->assertTrue($hr->hasPermission('leave.approve', $organization));
        $this->assertTrue($employee->hasPermission('ess.access', $organization));
        $this->assertFalse($employee->hasPermission('hrms.view', $organization));
        $this->assertFalse($sales->hasPermission('hrms.view', $organization));
        $this->assertFalse($sales->hasPermission('ess.access', $organization));
    }

    public function test_tenant_scope_isolates_employee_and_org_structure_models(): void
    {
        $first = Organization::factory()->create();
        $second = Organization::factory()->create();
        app(TenantContext::class)->set($first);

        Branch::factory()->create(['organization_id' => $first->id]);
        Branch::factory()->create(['organization_id' => $second->id]);
        Employee::factory()->create(['organization_id' => $first->id]);
        Employee::factory()->create(['organization_id' => $second->id]);

        $this->assertSame(1, Branch::query()->count());
        $this->assertSame(1, Employee::query()->count());

        app(TenantContext::class)->set($second);
        $this->assertSame(1, Branch::query()->count());
        $this->assertSame(1, Employee::query()->count());
    }

    public function test_factories_generate_core_hrms_models(): void
    {
        $organization = Organization::factory()->create();
        app(TenantContext::class)->set($organization);

        $branch = Branch::factory()->create(['organization_id' => $organization->id]);
        $department = Department::factory()->create([
            'organization_id' => $organization->id,
            'branch_id' => $branch->id,
        ]);
        $designation = Designation::factory()->create(['organization_id' => $organization->id]);
        $team = HrmsTeam::factory()->create([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
        ]);
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
        ]);
        $shift = HrmsShift::factory()->create(['organization_id' => $organization->id]);
        $leaveType = LeaveType::factory()->create(['organization_id' => $organization->id]);
        $holiday = Holiday::factory()->create(['organization_id' => $organization->id]);

        $this->assertTrue($employee->branch->is($branch));
        $this->assertTrue($employee->department->is($department));
        $this->assertTrue($employee->designation->is($designation));
        $this->assertTrue($team->department->is($department));
        $this->assertNotNull($shift->id);
        $this->assertNotNull($leaveType->id);
        $this->assertNotNull($holiday->id);
    }

    public function test_employee_relationships_and_ess_context_resolution(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'employee');
        app(TenantContext::class)->set($organization);

        $manager = Employee::factory()->create(['organization_id' => $organization->id]);
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'reporting_manager_id' => $manager->id,
        ]);

        $this->assertTrue($employee->reportingManager->is($manager));
        $this->assertTrue($manager->directReports->contains($employee));
        $this->assertTrue($employee->user->is($user));

        $resolved = app(EssContext::class)->employeeFor($user);
        $this->assertTrue($resolved?->is($employee));
    }

    public function test_policies_authorize_hrms_permissions(): void
    {
        $organization = Organization::factory()->create();
        $hr = User::factory()->create();
        $employeeUser = User::factory()->create();
        $organization->addMember($hr, 'hr');
        $organization->addMember($employeeUser, 'employee');
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $branch = Branch::factory()->create(['organization_id' => $organization->id]);
        $document = EmployeeDocument::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'category' => 'pan',
            'title' => 'PAN Card',
            'verification_status' => 'pending',
        ]);

        $this->assertTrue(Gate::forUser($hr)->allows('viewAny', Employee::class));
        $this->assertTrue(Gate::forUser($hr)->allows('view', $employee));
        $this->assertTrue(Gate::forUser($hr)->allows('manage', $document));
        $this->assertTrue(Gate::forUser($hr)->allows('view', $branch));

        $this->assertFalse(Gate::forUser($employeeUser)->allows('viewAny', Employee::class));
        $this->assertFalse(Gate::forUser($employeeUser)->allows('manage', $document));
        $this->assertTrue($employeeUser->hasPermission('ess.access', $organization));
    }

    public function test_leave_and_attendance_policies(): void
    {
        $organization = Organization::factory()->create();
        $manager = User::factory()->create();
        $organization->addMember($manager, 'manager');
        app(TenantContext::class)->set($organization);

        $managerEmployee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $manager->id,
        ]);
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'reporting_manager_id' => $managerEmployee->id,
        ]);
        $leaveType = LeaveType::factory()->create(['organization_id' => $organization->id]);
        $leave = LeaveApplication::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'days' => 1,
            'status' => 'pending',
        ]);
        LeaveApprovalStep::query()->create([
            'organization_id' => $organization->id,
            'leave_application_id' => $leave->id,
            'step_order' => 1,
            'approver_employee_id' => $managerEmployee->id,
            'approver_user_id' => $manager->id,
            'status' => 'pending',
        ]);
        $attendance = AttendanceRecord::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $this->assertTrue(Gate::forUser($manager)->allows('view', $leave));
        $this->assertTrue(Gate::forUser($manager)->allows('approve', $leave));
        $this->assertTrue(Gate::forUser($manager)->allows('view', $attendance));
        $this->assertTrue(Gate::forUser($manager)->allows('correct', $attendance));
        $this->assertFalse(Gate::forUser($manager)->allows('manage', $leave));
    }

    public function test_hrms_and_ess_routes_are_permission_protected(): void
    {
        // HRMS is licensed on professional/enterprise only (starter → Module not licensed).
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $hr = User::factory()->create();
        $employee = User::factory()->create();
        $sales = User::factory()->create();
        $organization->addMember($hr, 'hr');
        $organization->addMember($employee, 'employee');
        $organization->addMember($sales, 'sales-executive');

        $this->actingAs($hr)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.dashboard'))
            ->assertOk()
            ->assertSee('HR Dashboard');

        $this->actingAs($sales)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.dashboard'))
            ->assertForbidden();

        Employee::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'status' => 'active',
        ]);

        $this->actingAs($employee)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('ess.dashboard'))
            ->assertOk()
            ->assertSee('My HR Dashboard');

        $this->actingAs($sales)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('ess.dashboard'))
            ->assertForbidden();
    }

    public function test_sidebar_shows_hrms_and_ess_links_based_on_permissions(): void
    {
        // Shell nav is workspace-scoped; assert HRMS workspace visibility by role.
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $hr = User::factory()->create();
        $employee = User::factory()->create();
        $sales = User::factory()->create();
        $organization->addMember($hr, 'hr');
        $organization->addMember($employee, 'employee');
        $organization->addMember($sales, 'sales-executive');

        $workspaceIds = fn (User $user) => app(NavigationService::class)
            ->availableWorkspaces($user, $organization)
            ->pluck('id');

        $this->assertTrue($workspaceIds($hr)->contains('hr'));
        $this->assertTrue($workspaceIds($employee)->contains('hr'));
        $this->assertFalse($workspaceIds($sales)->contains('hr'));

        $this->actingAs($hr)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('HRMS', false);

        $this->actingAs($employee)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('HRMS', false);

        $this->actingAs($sales)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('>HRMS<', false);
    }

    public function test_service_skeletons_are_resolvable(): void
    {
        foreach ([
            EmployeeService::class,
            BranchService::class,
            DepartmentService::class,
            DesignationService::class,
            TeamService::class,
            EmployeeDocumentService::class,
            AttendanceService::class,
            LeaveService::class,
            HrmsDashboardService::class,
            EssContext::class,
        ] as $service) {
            $this->assertInstanceOf($service, app($service));
        }
    }

    public function test_hrms_config_catalogs_are_present(): void
    {
        $this->assertNotEmpty(config('hrms.employment_statuses'));
        $this->assertNotEmpty(config('hrms.employment_types'));
        $this->assertNotEmpty(config('hrms.attendance_statuses'));
        $this->assertNotEmpty(config('hrms.leave_statuses'));
        $this->assertNotEmpty(config('hrms.default_leave_types'));
        $this->assertNotEmpty(config('hrms.document_categories'));
        $this->assertNotEmpty(config('hrms.identity_document_types'));
        $this->assertNotEmpty(config('hrms.shift_presets'));
        $this->assertNotEmpty(config('hrms.working_days'));
        $this->assertNotEmpty(config('hrms.weekend_days'));
        $this->assertArrayHasKey('employee.created', config('hrms.workflow_triggers'));
        $this->assertArrayHasKey('employee.created', config('workflows.triggers'));
        $this->assertSame(
            config('hrms.workflow_triggers.employee.created'),
            config('workflows.triggers.employee.created'),
        );
    }
}
