<?php

namespace Tests\Feature;

use App\Events\PayrollEmployeeCalculated;
use App\Events\PayrollRunCompleted;
use App\Events\PayrollRunStarted;
use App\Events\PayrollValidationFailed;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\PayrollPeriod;
use App\Models\PayrollResult;
use App\Models\PayrollRun;
use App\Models\Permission;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Services\Hrms\PayrollCalculationService;
use App\Services\Hrms\PayrollService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\Support\LocksAttendanceForPayroll;
use Tests\TestCase;

class HrmsPayrollCalculationTest extends TestCase
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

    public function test_payroll_calculation_tables_exist(): void
    {
        foreach (['payroll_runs', 'payroll_results', 'payroll_validation_errors'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_payroll_calculate_permission_is_seeded_for_hr(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();

        $this->assertNotNull(Permission::query()->where('slug', 'payroll.calculate')->first());
        $this->assertTrue($hr->hasPermission('payroll.calculate', $organization));

        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');
        $this->assertFalse($employee->hasPermission('payroll.calculate', $organization));
    }

    public function test_payroll_run_creation_calculation_and_audit(): void
    {
        Event::fake([
            PayrollRunStarted::class,
            PayrollRunCompleted::class,
            PayrollEmployeeCalculated::class,
        ]);

        [$organization, $hr, $period, $employee] = $this->payrollScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.payroll.runs.store'), [
            'payroll_period_id' => $period->id,
        ])->assertRedirect();

        $run = PayrollRun::query()->firstOrFail();
        $this->assertSame('draft', $run->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payroll_run_created']);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.runs.calculate', $run))
            ->assertRedirect(route('hrms.payroll.runs.show', $run));

        $run->refresh();
        $this->assertSame('calculated', $run->status);
        $this->assertSame(1, $run->success_count);
        $this->assertDatabaseHas('payroll_results', [
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payroll_calculation_started']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payroll_calculation_completed']);

        Event::assertDispatched(PayrollRunStarted::class);
        Event::assertDispatched(PayrollRunCompleted::class);
        Event::assertDispatched(PayrollEmployeeCalculated::class);

        $result = PayrollResult::query()->firstOrFail();
        $this->assertNotEmpty($result->calculation_hash);
        $this->assertIsArray($result->snapshot);
        $this->assertSame('10.3.6', $result->snapshot['engine_version']);
        $this->assertGreaterThan(0, (float) $result->gross_salary);
        $this->assertSame((float) $result->net_salary, (float) $result->gross_salary - (float) $result->total_deductions);
    }

    public function test_preview_does_not_persist_and_matches_engine_hash(): void
    {
        [$organization, $hr, $period, $employee] = $this->payrollScenario();
        app(TenantContext::class)->set($organization);

        $preview = app(PayrollCalculationService::class)->previewEmployee($employee, $period);
        $this->assertSame([], $preview['validation_errors']);
        $this->assertNotNull($preview['calculation']);
        $this->assertDatabaseCount('payroll_results', 0);

        $calculation = app(PayrollCalculationService::class)->calculateEmployeePayroll($employee, $period);
        $this->assertSame($preview['calculation']['calculation_hash'], $calculation['calculation_hash']);
        $this->assertSame($preview['calculation']['net_salary'], $calculation['net_salary']);

        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->post(route('hrms.payroll.runs.preview.submit'), [
                'payroll_period_id' => $period->id,
                'employee_id' => $employee->id,
            ])
            ->assertOk()
            ->assertSee((string) number_format($calculation['net_salary'], 2));

        $this->assertDatabaseCount('payroll_results', 0);
    }

    public function test_validation_errors_for_missing_salary_assignment(): void
    {
        Event::fake([PayrollValidationFailed::class, PayrollRunCompleted::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);
        $session = ['current_organization_id' => $organization->id];

        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'active',
            'joining_date' => '2026-01-01',
        ]);
        $period = PayrollPeriod::factory()->open()->create([
            'organization_id' => $organization->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ]);

        $run = app(PayrollCalculationService::class)->createRun($period, $hr);
        app(PayrollCalculationService::class)->calculateRun($run, $hr);

        $run->refresh();
        $this->assertSame(0, $run->success_count);
        $this->assertGreaterThan(0, $run->error_count);
        $this->assertDatabaseHas('payroll_validation_errors', [
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'code' => 'salary_assignment_missing',
        ]);
        Event::assertDispatched(PayrollValidationFailed::class);

        $this->actingAs($hr)->withSession($session)
            ->get(route('hrms.payroll.runs.show', $run))
            ->assertOk()
            ->assertSee('salary_assignment_missing');
    }

    public function test_recalculation_allowed_for_running_and_blocked_when_calculated(): void
    {
        Event::fake([PayrollRunStarted::class, PayrollRunCompleted::class, PayrollEmployeeCalculated::class]);

        [$organization, $hr, $period, $employee] = $this->payrollScenario();
        $session = ['current_organization_id' => $organization->id];
        $service = app(PayrollCalculationService::class);

        $run = $service->createRun($period, $hr);
        $run->update(['status' => 'running']);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.runs.recalculate', $run))
            ->assertRedirect(route('hrms.payroll.runs.show', $run));

        $run->refresh();
        $this->assertSame('calculated', $run->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payroll_recalculated']);
        $this->assertDatabaseHas('payroll_results', [
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
        ]);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.runs.recalculate', $run))
            ->assertSessionHasErrors('run');
    }

    public function test_duplicate_result_prevention_and_immutable_snapshot(): void
    {
        [$organization, $hr, $period, $employee] = $this->payrollScenario();
        app(TenantContext::class)->set($organization);
        $service = app(PayrollCalculationService::class);

        $run = $service->createRun($period, $hr);
        $service->calculateRun($run, $hr);

        $result = PayrollResult::query()->firstOrFail();
        $originalHash = $result->calculation_hash;
        $originalSnapshot = $result->snapshot;

        try {
            PayrollResult::query()->create([
                'organization_id' => $organization->id,
                'payroll_run_id' => $run->id,
                'employee_id' => $employee->id,
                'gross_salary' => 1,
                'total_earnings' => 1,
                'total_deductions' => 0,
                'net_salary' => 1,
                'working_days' => 26,
                'payable_days' => 26,
                'overtime_minutes' => 0,
                'overtime_amount' => 0,
                'snapshot' => ['tampered' => true],
                'calculation_hash' => 'x',
                'version' => 2,
            ]);
            $this->fail('Expected unique constraint violation for duplicate payroll result.');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }

        $result->refresh();
        $this->assertSame($originalHash, $result->calculation_hash);
        $this->assertEquals($originalSnapshot, $result->snapshot);
    }

    public function test_locked_period_cannot_be_calculated(): void
    {
        [$organization, $hr, $period] = array_slice($this->payrollScenario(), 0, 3);
        $session = ['current_organization_id' => $organization->id];
        $period->update(['status' => 'locked']);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.payroll.runs.store'), [
            'payroll_period_id' => $period->id,
        ])->assertSessionHasErrors('period');
    }

    public function test_tenant_isolation_for_payroll_runs(): void
    {
        [$organizationA, $hrA, $periodA] = array_slice($this->payrollScenario(), 0, 3);
        [$organizationB, $hrB] = $this->organizationWithHrUser();

        app(TenantContext::class)->set($organizationA);
        $runA = app(PayrollCalculationService::class)->createRun($periodA, $hrA);

        app(TenantContext::class)->set($organizationB);
        $this->assertSame(0, PayrollRun::query()->count());

        $this->actingAs($hrB)->withSession(['current_organization_id' => $organizationB->id])
            ->get(route('hrms.payroll.runs.show', $runA))
            ->assertNotFound();
    }

    public function test_employee_cannot_access_payroll_calculation(): void
    {
        $organization = Organization::factory()->create();
        $employeeUser = User::factory()->create();
        $organization->addMember($employeeUser, 'employee');
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($employeeUser)->withSession($session)
            ->get(route('hrms.payroll.runs.index'))
            ->assertForbidden();

        $this->actingAs($employeeUser)->withSession($session)
            ->get(route('hrms.payroll.runs.preview'))
            ->assertForbidden();
    }

    public function test_fixed_and_percentage_earnings_are_deterministic(): void
    {
        [$organization, $hr, $period, $employee] = $this->payrollScenario(withPercentage: true);
        app(TenantContext::class)->set($organization);

        $first = app(PayrollCalculationService::class)->calculateEmployeePayroll($employee, $period);
        $second = app(PayrollCalculationService::class)->calculateEmployeePayroll($employee, $period);

        $this->assertSame($first['calculation_hash'], $second['calculation_hash']);
        $this->assertSame($first['gross_salary'], $second['gross_salary']);
        $this->assertSame($first['net_salary'], $second['net_salary']);

        // Basic 20000 + HRA 40% of basic = 8000 => 28000 (full month, no unpaid leave)
        $this->assertEqualsWithDelta(28000.0, (float) $first['gross_salary'], 0.01);
    }

    /**
     * @return array{0: Organization, 1: User, 2: PayrollPeriod, 3: Employee}
     */
    private function payrollScenario(bool $withPercentage = false): array
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'active',
            'joining_date' => '2026-01-01',
        ]);

        $basic = SalaryComponent::factory()->earning()->create([
            'organization_id' => $organization->id,
            'name' => 'Basic',
            'code' => 'BASIC',
            'is_recurring' => true,
        ]);

        $components = [[
            'salary_component_id' => $basic->id,
            'calculation_type' => 'fixed',
            'amount' => 20000,
        ]];

        if ($withPercentage) {
            $hra = SalaryComponent::factory()->earning()->create([
                'organization_id' => $organization->id,
                'name' => 'HRA',
                'code' => 'HRA',
                'is_recurring' => true,
            ]);
            $components[] = [
                'salary_component_id' => $hra->id,
                'calculation_type' => 'percentage',
                'percentage' => 40,
                'based_on_component_id' => $basic->id,
            ];
        }

        $structure = app(PayrollService::class)->createSalaryStructure([
            'name' => 'Calc Structure '.fake()->unique()->numerify('###'),
            'effective_date' => '2026-01-01',
            'is_active' => true,
            'components' => $components,
        ], $hr);

        app(PayrollService::class)->assignSalaryStructure($employee, [
            'salary_structure_id' => $structure->id,
            'effective_from' => '2026-01-01',
            'annual_ctc' => 480000,
        ], $hr);

        app(TenantContext::class)->set($organization);
        app(PayrollService::class)->getOrCreateConfiguration();

        $period = PayrollPeriod::factory()->open()->create([
            'organization_id' => $organization->id,
            'name' => 'July 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ]);

        $this->lockAttendanceForPayrollPeriod($period, $hr);
        app(TenantContext::class)->set($organization);

        return [$organization, $hr, $period, $employee];
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
