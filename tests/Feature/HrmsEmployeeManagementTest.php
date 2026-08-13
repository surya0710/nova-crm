<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\HrmsTeam;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrmsEmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_structure_crud_routes_work(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.branches.store'), [
            'name' => 'HQ', 'code' => 'HQ',
        ])->assertRedirect(route('hrms.branches.index'));
        $branch = Branch::query()->firstOrFail();
        $this->actingAs($hr)->withSession($session)->put(route('hrms.branches.update', $branch), [
            'name' => 'HQ Updated', 'code' => 'HQ',
        ])->assertRedirect(route('hrms.branches.index'));

        $this->actingAs($hr)->withSession($session)->post(route('hrms.departments.store'), [
            'name' => 'Engineering', 'code' => 'ENG', 'branch_id' => $branch->id,
        ])->assertRedirect(route('hrms.departments.index'));
        $department = Department::query()->firstOrFail();
        $this->actingAs($hr)->withSession($session)->put(route('hrms.departments.update', $department), [
            'name' => 'Engineering Updated', 'code' => 'ENG', 'branch_id' => $branch->id,
        ])->assertRedirect(route('hrms.departments.index'));

        $this->actingAs($hr)->withSession($session)->post(route('hrms.designations.store'), [
            'name' => 'Engineer', 'code' => 'SE', 'department_id' => $department->id,
        ])->assertRedirect(route('hrms.designations.index'));
        $designation = Designation::query()->firstOrFail();
        $this->actingAs($hr)->withSession($session)->put(route('hrms.designations.update', $designation), [
            'name' => 'Engineer II', 'code' => 'SE2', 'department_id' => $department->id,
        ])->assertRedirect(route('hrms.designations.index'));

        $lead = Employee::factory()->create(['organization_id' => $organization->id]);
        $this->actingAs($hr)->withSession($session)->post(route('hrms.teams.store'), [
            'name' => 'Core Team', 'code' => 'CORE', 'department_id' => $department->id, 'team_lead_employee_id' => $lead->id,
        ])->assertRedirect(route('hrms.teams.index'));
        $team = HrmsTeam::query()->firstOrFail();
        $this->actingAs($hr)->withSession($session)->put(route('hrms.teams.update', $team), [
            'name' => 'Core Team Updated', 'code' => 'CORE2', 'department_id' => $department->id, 'team_lead_employee_id' => $lead->id,
        ])->assertRedirect(route('hrms.teams.index'));
        $this->actingAs($hr)->withSession($session)->delete(route('hrms.teams.destroy', $team))->assertRedirect(route('hrms.teams.index'));

        $this->assertDatabaseHas('hrms_branches', ['id' => $branch->id]);
        $this->assertDatabaseHas('hrms_departments', ['id' => $department->id]);
        $this->assertDatabaseHas('hrms_designations', ['id' => $designation->id]);
        $this->assertDatabaseHas('hrms_teams', ['name' => 'Core Team Updated']);
    }

    public function test_employee_crud_user_link_and_audit_entries(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        $branch = Branch::factory()->create(['organization_id' => $organization->id]);
        $department = Department::factory()->create(['organization_id' => $organization->id, 'branch_id' => $branch->id]);
        $designation = Designation::factory()->create(['organization_id' => $organization->id]);

        $payload = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'employment_type' => 'full_time',
            'status' => 'active',
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
        ];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.employees.store'), $payload)->assertRedirect();
        $employee = Employee::query()->firstOrFail();
        $this->assertStringStartsWith(config('hrms.employee_code.prefix', 'EMP').'-', $employee->employee_code);

        $this->actingAs($hr)->withSession($session)->put(route('hrms.employees.update', $employee), [
            ...$payload,
            'status' => 'probation',
        ])->assertRedirect();

        $linkUser = User::factory()->create();
        $organization->addMember($linkUser, 'employee');
        $this->actingAs($hr)->withSession($session)->post(route('hrms.employees.link-user', $employee), [
            'user_id' => $linkUser->id,
        ])->assertRedirect();
        $employee->refresh();
        $this->assertSame($linkUser->id, $employee->user_id);

        $this->actingAs($hr)->withSession($session)->delete(route('hrms.employees.unlink-user', $employee))->assertRedirect();
        $this->assertNull($employee->fresh()->user_id);

        $this->assertDatabaseHas('audit_logs', ['event' => 'employee_created']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'employee_updated']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'employee_user_linked']);
    }

    public function test_employee_code_is_unique_within_organization(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.employees.store'), [
            'first_name' => 'A', 'employment_type' => 'full_time', 'status' => 'active',
        ]);
        $this->actingAs($hr)->withSession($session)->post(route('hrms.employees.store'), [
            'first_name' => 'B', 'employment_type' => 'full_time', 'status' => 'active',
        ]);

        $codes = Employee::query()->pluck('employee_code');
        $this->assertCount(2, $codes->unique());
    }

    public function test_reporting_hierarchy_blocks_self_reporting_and_circular_chain(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        $a = Employee::factory()->create(['organization_id' => $organization->id]);
        $b = Employee::factory()->create(['organization_id' => $organization->id, 'reporting_manager_id' => $a->id]);

        $this->actingAs($hr)->withSession($session)
            ->put(route('hrms.employees.update', $a), [
                'first_name' => $a->first_name,
                'employment_type' => $a->employment_type,
                'status' => $a->status,
                'reporting_manager_id' => $a->id,
            ])->assertSessionHasErrors('reporting_manager_id');

        $this->actingAs($hr)->withSession($session)
            ->put(route('hrms.employees.update', $a), [
                'first_name' => $a->first_name,
                'employment_type' => $a->employment_type,
                'status' => $a->status,
                'reporting_manager_id' => $b->id,
            ])->assertSessionHasErrors('reporting_manager_id');
    }

    public function test_cross_organization_hrms_resource_access_returns_404(): void
    {
        [$orgA, $hrA] = $this->organizationWithHrUser();
        [$orgB] = $this->organizationWithHrUser();
        $employeeB = Employee::factory()->create(['organization_id' => $orgB->id]);

        $this->actingAs($hrA)
            ->withSession(['current_organization_id' => $orgA->id])
            ->get(route('hrms.employees.show', $employeeB))
            ->assertForbidden();
    }

    public function test_hr_rbac_allowed_and_regular_employee_forbidden(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employeeUser = User::factory()->create();
        $organization->addMember($employeeUser, 'employee');

        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.employees.index'))->assertOk();

        $this->actingAs($employeeUser)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.employees.index'))->assertForbidden();
    }

    private function organizationWithHrUser(): array
    {
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        return [$organization, $user];
    }
}
