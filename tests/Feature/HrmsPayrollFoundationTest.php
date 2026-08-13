<?php

namespace Tests\Feature;

use App\Events\EmployeeSalaryAssigned;
use App\Events\PayrollPeriodCreated;
use App\Events\PayrollPeriodLocked;
use App\Events\SalaryStructureCreated;
use App\Events\SalaryStructureUpdated;
use App\Models\Employee;
use App\Models\EmployeeSalaryAssignment;
use App\Models\Organization;
use App\Models\PayrollPeriod;
use App\Models\Permission;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Models\User;
use App\Services\Hrms\PayrollService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\Support\LocksAttendanceForPayroll;
use Tests\TestCase;

class HrmsPayrollFoundationTest extends TestCase
{
    use LocksAttendanceForPayroll;
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

    public function test_payroll_tables_exist(): void
    {
        foreach ([
            'salary_components',
            'salary_structures',
            'salary_structure_components',
            'employee_salary_assignments',
            'payroll_periods',
            'payroll_configurations',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_payroll_permissions_are_seeded_for_hr(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();

        foreach (['payroll.view', 'payroll.manage', 'payroll.configuration'] as $slug) {
            $this->assertNotNull(Permission::query()->where('slug', $slug)->first(), "Missing permission: {$slug}");
            $this->assertTrue($hr->hasPermission($slug, $organization));
        }

        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');

        $this->assertFalse($employee->hasPermission('payroll.view', $organization));
        $this->assertFalse($employee->hasPermission('payroll.manage', $organization));
    }

    public function test_salary_component_crud_and_audit(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.payroll.components.store'), [
            'name' => 'Basic',
            'code' => 'BASIC',
            'component_type' => 'earning',
            'is_taxable' => true,
            'is_recurring' => true,
        ])->assertRedirect(route('hrms.payroll.components.index'));

        $this->assertDatabaseHas('salary_components', [
            'code' => 'BASIC',
            'organization_id' => $organization->id,
            'component_type' => 'earning',
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'salary_component_created']);

        $component = SalaryComponent::query()->where('code', 'BASIC')->firstOrFail();

        $this->actingAs($hr)->withSession($session)->put(route('hrms.payroll.components.update', $component), [
            'name' => 'Basic Pay',
            'code' => 'BASIC',
            'component_type' => 'earning',
            'is_taxable' => true,
            'is_recurring' => true,
            'is_active' => true,
        ])->assertRedirect(route('hrms.payroll.components.index'));

        $this->assertDatabaseHas('audit_logs', ['event' => 'salary_component_updated']);
        $this->assertDatabaseHas('salary_components', ['id' => $component->id, 'name' => 'Basic Pay']);

        $this->actingAs($hr)->withSession($session)->delete(route('hrms.payroll.components.destroy', $component))
            ->assertRedirect(route('hrms.payroll.components.index'));

        $this->assertSoftDeleted('salary_components', ['id' => $component->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'salary_component_deleted']);
    }

    public function test_salary_structure_crud_emits_workflow_events(): void
    {
        Event::fake([SalaryStructureCreated::class, SalaryStructureUpdated::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        $component = SalaryComponent::factory()->earning()->create(['organization_id' => $organization->id]);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.payroll.structures.store'), [
            'name' => 'Standard Grade A',
            'effective_date' => '2026-07-01',
            'is_active' => true,
            'components' => [
                [
                    'salary_component_id' => $component->id,
                    'calculation_type' => 'fixed',
                    'amount' => 25000,
                ],
            ],
        ])->assertRedirect(route('hrms.payroll.structures.index'));

        $structure = SalaryStructure::query()->where('name', 'Standard Grade A')->firstOrFail();
        $this->assertDatabaseHas('salary_structure_components', [
            'salary_structure_id' => $structure->id,
            'salary_component_id' => $component->id,
            'calculation_type' => 'fixed',
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'salary_structure_created']);
        Event::assertDispatched(SalaryStructureCreated::class);

        $this->actingAs($hr)->withSession($session)->put(route('hrms.payroll.structures.update', $structure), [
            'name' => 'Standard Grade A Updated',
            'effective_date' => '2026-07-01',
            'is_active' => true,
            'components' => [
                [
                    'salary_component_id' => $component->id,
                    'calculation_type' => 'percentage',
                    'percentage' => 40,
                ],
            ],
        ])->assertRedirect(route('hrms.payroll.structures.index'));

        $this->assertDatabaseHas('audit_logs', ['event' => 'salary_structure_updated']);
        Event::assertDispatched(SalaryStructureUpdated::class);

        $this->actingAs($hr)->withSession($session)->delete(route('hrms.payroll.structures.destroy', $structure))
            ->assertRedirect(route('hrms.payroll.structures.index'));

        $this->assertSoftDeleted('salary_structures', ['id' => $structure->id]);
    }

    public function test_employee_salary_assignment_preserves_history(): void
    {
        Event::fake([EmployeeSalaryAssigned::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $structureA = SalaryStructure::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Structure A',
        ]);
        $structureB = SalaryStructure::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Structure B',
        ]);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.payroll.assignments.store'), [
            'employee_id' => $employee->id,
            'salary_structure_id' => $structureA->id,
            'effective_from' => '2026-01-01',
            'annual_ctc' => 600000,
        ])->assertRedirect(route('hrms.payroll.assignments.index'));

        $first = EmployeeSalaryAssignment::query()->where('salary_structure_id', $structureA->id)->firstOrFail();
        $this->assertNull($first->effective_until);
        $this->assertDatabaseHas('audit_logs', ['event' => 'employee_salary_assigned']);
        Event::assertDispatched(EmployeeSalaryAssigned::class);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.payroll.assignments.store'), [
            'employee_id' => $employee->id,
            'salary_structure_id' => $structureB->id,
            'effective_from' => '2026-07-01',
            'annual_ctc' => 720000,
        ])->assertRedirect(route('hrms.payroll.assignments.index'));

        $first->refresh();
        $this->assertSame('2026-06-30', $first->effective_until->toDateString());
        $this->assertDatabaseHas('employee_salary_assignments', [
            'id' => $first->id,
            'salary_structure_id' => $structureA->id,
            'effective_from' => '2026-01-01',
        ]);
        $this->assertSame(2, EmployeeSalaryAssignment::query()->where('employee_id', $employee->id)->count());

        $this->actingAs($hr)->withSession($session)->post(route('hrms.payroll.assignments.store'), [
            'employee_id' => $employee->id,
            'salary_structure_id' => $structureA->id,
            'effective_from' => '2026-03-01',
        ])->assertSessionHasErrors('effective_from');
    }

    public function test_payroll_period_create_and_lock_workflow(): void
    {
        Event::fake([PayrollPeriodCreated::class, PayrollPeriodLocked::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.payroll.periods.store'), [
            'name' => 'July 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'status' => 'open',
        ])->assertRedirect(route('hrms.payroll.periods.index'));

        $period = PayrollPeriod::query()->firstOrFail();
        $this->assertSame('open', $period->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payroll_period_created']);
        Event::assertDispatched(PayrollPeriodCreated::class);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.payroll.periods.lock', $period))
            ->assertRedirect(route('hrms.payroll.periods.index'));

        $period->refresh();
        $this->assertSame('locked', $period->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payroll_period_locked']);
        Event::assertDispatched(PayrollPeriodLocked::class);
    }

    public function test_payroll_configuration_update_and_audit(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->put(route('hrms.payroll.configuration.update'), [
            'payroll_frequency' => 'monthly',
            'currency' => 'INR',
            'working_days_per_month' => 26,
            'week_off_days' => ['saturday', 'sunday'],
            'overtime_handling' => 'pay',
            'rounding_policy' => 'nearest',
            'salary_mode' => 'calendar',
        ])->assertRedirect(route('hrms.payroll.configuration.edit'));

        $this->assertDatabaseHas('payroll_configurations', [
            'organization_id' => $organization->id,
            'currency' => 'INR',
            'working_days_per_month' => 26,
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payroll_configuration_updated']);
    }

    public function test_tenant_isolation_for_payroll_resources(): void
    {
        [$organizationA, $hrA] = $this->organizationWithHrUser();
        [$organizationB, $hrB] = $this->organizationWithHrUser();

        $componentA = SalaryComponent::factory()->create(['organization_id' => $organizationA->id, 'code' => 'ORG-A']);
        SalaryComponent::factory()->create(['organization_id' => $organizationB->id, 'code' => 'ORG-B']);

        app(TenantContext::class)->set($organizationA);
        $this->assertSame(1, SalaryComponent::query()->count());
        $this->assertTrue(SalaryComponent::query()->whereKey($componentA->id)->exists());

        $this->actingAs($hrB)->withSession(['current_organization_id' => $organizationB->id])
            ->get(route('hrms.payroll.components.index'))
            ->assertOk()
            ->assertDontSee('ORG-A')
            ->assertSee('ORG-B');
    }

    public function test_employee_role_cannot_access_payroll(): void
    {
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $employeeUser = User::factory()->create();
        $organization->addMember($employeeUser, 'employee');
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($employeeUser)->withSession($session)
            ->get(route('hrms.payroll.index'))
            ->assertForbidden();

        $this->actingAs($employeeUser)->withSession($session)
            ->get(route('hrms.payroll.components.index'))
            ->assertForbidden();

        $this->actingAs($employeeUser)->withSession($session)
            ->get(route('hrms.payroll.configuration.edit'))
            ->assertForbidden();
    }

    public function test_calculation_context_contract_resolves_inputs_without_calculating(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'active',
            'joining_date' => '2026-01-01',
        ]);
        $structure = SalaryStructure::factory()->create(['organization_id' => $organization->id]);
        $component = SalaryComponent::factory()->earning()->create(['organization_id' => $organization->id]);

        app(PayrollService::class)->createSalaryStructure([
            'name' => 'Contract Structure',
            'effective_date' => '2026-01-01',
            'is_active' => true,
            'components' => [[
                'salary_component_id' => $component->id,
                'calculation_type' => 'fixed',
                'amount' => 30000,
            ]],
        ], $hr);

        $created = SalaryStructure::query()->where('name', 'Contract Structure')->firstOrFail();
        app(PayrollService::class)->assignSalaryStructure($employee, [
            'salary_structure_id' => $created->id,
            'effective_from' => '2026-01-01',
            'annual_ctc' => 500000,
        ], $hr);

        $period = PayrollPeriod::factory()->create([
            'organization_id' => $organization->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'status' => 'open',
        ]);

        $this->lockAttendanceForPayrollPeriod($period, $hr);

        $context = app(PayrollService::class)->resolveCalculationContext($employee, $period);

        $this->assertSame($employee->id, $context['employee']['id']);
        $this->assertNotNull($context['salary_assignment']);
        $this->assertSame('deferred', $context['calculation_status']);
        $this->assertNull($context['calculation']);
        $this->assertArrayHasKey('attendance', $context);
        $this->assertArrayHasKey('leave', $context);
        $this->assertArrayHasKey('exit', $context);
    }

    /** @return array{0: Organization, 1: User} */
    private function organizationWithHrUser(): array
    {
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        return [$organization, $user];
    }
}
