<?php

namespace Tests\Feature;

use App\Events\PayrollComplianceFailed;
use App\Events\PayrollStatutoryCalculated;
use App\Events\StatutoryProfileUpdated;
use App\Events\StatutoryRuleChanged;
use App\Models\Employee;
use App\Models\EmployeeStatutoryProfile;
use App\Models\Organization;
use App\Models\PayrollPeriod;
use App\Models\PayrollResult;
use App\Models\Permission;
use App\Models\SalaryComponent;
use App\Models\StatutoryRuleSet;
use App\Models\StatutoryRuleVersion;
use App\Models\User;
use App\Services\Hrms\PayrollCalculationService;
use App\Services\Hrms\PayrollService;
use App\Services\Hrms\StatutoryComplianceService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\Support\LocksAttendanceForPayroll;
use Tests\TestCase;

class HrmsPayrollStatutoryTest extends TestCase
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

    public function test_statutory_tables_exist(): void
    {
        foreach ([
            'statutory_rule_sets',
            'statutory_rule_versions',
            'employee_statutory_profiles',
            'statutory_compliance_errors',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_statutory_permissions_seeded_for_hr_only(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();

        foreach (['payroll.statutory.view', 'payroll.statutory.manage', 'payroll.statutory.configuration'] as $slug) {
            $this->assertNotNull(Permission::query()->where('slug', $slug)->first());
            $this->assertTrue($hr->hasPermission($slug, $organization));
        }

        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');
        $this->assertFalse($employee->hasPermission('payroll.statutory.view', $organization));
        $this->assertFalse($employee->hasPermission('payroll.statutory.manage', $organization));
    }

    public function test_pf_calculation_and_eligibility(): void
    {
        [$organization, $hr, $period, $employee, $profile] = $this->statutoryScenario([
            'basic' => 20000,
            'hra' => 8000,
            'pf_eligible' => true,
        ]);
        app(TenantContext::class)->set($organization);

        $calculation = app(PayrollCalculationService::class)->calculateEmployeePayroll($employee, $period);
        unset($calculation['_compliance_errors'], $calculation['_statutory_meta']);

        $statutory = $calculation['snapshot']['statutory'];
        $this->assertTrue($statutory['pf']['eligible']);
        // Wage base BASIC 20000 capped at 15000 * 12% = 1800
        $this->assertSame(1800.0, (float) $statutory['pf']['employee_amount']);
        $this->assertSame(1800.0, (float) $statutory['pf']['employer_amount']);

        $profile->update(['pf_eligible' => false]);
        $ineligible = app(PayrollCalculationService::class)->calculateEmployeePayroll($employee->fresh(), $period);
        $this->assertFalse($ineligible['snapshot']['statutory']['pf']['eligible']);
        $this->assertSame(0.0, (float) $ineligible['snapshot']['statutory']['pf']['employee_amount']);
    }

    public function test_esi_calculation_and_threshold(): void
    {
        [$organization, $hr, $period, $employee] = $this->statutoryScenario([
            'basic' => 10000,
            'hra' => 5000,
            'esi_eligible' => true,
        ]);
        app(TenantContext::class)->set($organization);

        // Gross 15000 <= 21000 threshold
        $below = app(PayrollCalculationService::class)->calculateEmployeePayroll($employee, $period);
        $this->assertTrue($below['snapshot']['statutory']['esi']['eligible']);
        $this->assertSame(112.5, (float) $below['snapshot']['statutory']['esi']['employee_amount']); // 15000 * 0.75%
        $this->assertSame(487.5, (float) $below['snapshot']['statutory']['esi']['employer_amount']); // 15000 * 3.25%

        // Raise earnings above threshold
        [$organization2, $hr2, $period2, $employee2] = $this->statutoryScenario([
            'basic' => 18000,
            'hra' => 5000,
            'esi_eligible' => true,
        ]);
        app(TenantContext::class)->set($organization2);
        $above = app(PayrollCalculationService::class)->calculateEmployeePayroll($employee2, $period2);
        $this->assertSame('skipped_above_threshold', $above['snapshot']['statutory']['esi']['status']);
        $this->assertSame(0.0, (float) $above['snapshot']['statutory']['esi']['employee_amount']);
    }

    public function test_professional_tax_slabs_and_exemption_month(): void
    {
        [$organization, $hr, $period, $employee] = $this->statutoryScenario([
            'basic' => 12000,
            'hra' => 0,
            'pt_state' => 'MH',
        ]);
        app(TenantContext::class)->set($organization);

        // July (month 7) — MH slab > 10000 => 200
        $july = app(PayrollCalculationService::class)->calculateEmployeePayroll($employee, $period);
        $this->assertSame(200.0, (float) $july['snapshot']['statutory']['professional_tax']['amount']);

        // February exemption month for MH
        $febPeriod = PayrollPeriod::factory()->open()->create([
            'organization_id' => $organization->id,
            'name' => 'February 2026',
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
        ]);
        $this->lockAttendanceForPayrollPeriod($febPeriod, $hr);
        $feb = app(PayrollCalculationService::class)->calculateEmployeePayroll($employee, $febPeriod);
        $this->assertSame('exempt_month', $feb['snapshot']['statutory']['professional_tax']['status']);
        $this->assertSame(0.0, (float) $feb['snapshot']['statutory']['professional_tax']['amount']);
    }

    public function test_rule_version_resolution_and_historical_reproducibility(): void
    {
        [$organization, $hr, $period, $employee] = $this->statutoryScenario(['basic' => 15000, 'hra' => 0]);
        app(TenantContext::class)->set($organization);

        $ruleSet = StatutoryRuleSet::query()->where('is_active', true)->firstOrFail();
        StatutoryRuleVersion::query()->where('statutory_rule_set_id', $ruleSet->id)->update([
            'effective_until' => '2026-06-30',
        ]);

        StatutoryRuleVersion::factory()->create([
            'organization_id' => $organization->id,
            'statutory_rule_set_id' => $ruleSet->id,
            'version' => '2026.2',
            'effective_from' => '2026-07-01',
            'effective_until' => null,
            'configuration' => array_replace_recursive(
                config('hrms.statutory.default_india_configuration'),
                ['pf' => ['employee_contribution_percent' => 10, 'employer_contribution_percent' => 10]]
            ),
        ]);

        $service = app(StatutoryComplianceService::class);
        $resolved = $service->resolveRuleVersion($ruleSet, Carbon::parse('2026-07-20'));
        $this->assertSame('2026.2', $resolved?->version);

        $historical = $service->resolveRuleVersion($ruleSet, Carbon::parse('2026-03-15'));
        $this->assertSame('2026.1', $historical?->version);

        $first = app(PayrollCalculationService::class)->calculateEmployeePayroll($employee, $period);
        $second = app(PayrollCalculationService::class)->calculateEmployeePayroll($employee, $period);
        unset($first['_compliance_errors'], $first['_statutory_meta'], $second['_compliance_errors'], $second['_statutory_meta']);
        $this->assertSame($first['calculation_hash'], $second['calculation_hash']);
        $this->assertSame(1500.0, (float) $first['snapshot']['statutory']['pf']['employee_amount']); // 15000 * 10%
    }

    public function test_compliance_validation_workflow_and_audit(): void
    {
        Event::fake([
            StatutoryProfileUpdated::class,
            StatutoryRuleChanged::class,
            PayrollComplianceFailed::class,
            PayrollStatutoryCalculated::class,
        ]);

        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.statutory.rules.seed-india'))
            ->assertRedirect(route('hrms.payroll.statutory.rules'));

        $this->assertDatabaseHas('statutory_rule_sets', [
            'organization_id' => $organization->id,
            'code' => 'india_2026',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'statutory_rule_set_created']);
        Event::assertDispatched(StatutoryRuleChanged::class);

        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.payroll.statutory.profiles.store'), [
            'employee_id' => $employee->id,
            'pf_eligible' => '1',
            'pf_uan' => '',
            'esi_eligible' => '1',
            'esi_number' => '',
            'professional_tax_state' => 'MH',
            'tax_regime' => 'new',
            'pan' => '',
        ])->assertRedirect(route('hrms.payroll.statutory.profiles'));

        Event::assertDispatched(StatutoryProfileUpdated::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'statutory_profile_updated']);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.statutory.validation.run'))
            ->assertRedirect(route('hrms.payroll.statutory.validation'));

        $this->assertDatabaseHas('statutory_compliance_errors', [
            'employee_id' => $employee->id,
            'code' => 'missing_uan',
        ]);
        $this->assertDatabaseHas('statutory_compliance_errors', [
            'employee_id' => $employee->id,
            'code' => 'missing_esi_number',
        ]);
        $this->assertDatabaseHas('statutory_compliance_errors', [
            'employee_id' => $employee->id,
            'code' => 'missing_pan',
        ]);
        Event::assertDispatched(PayrollComplianceFailed::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'statutory_compliance_failed']);
    }

    public function test_payroll_integration_emits_statutory_calculated(): void
    {
        Event::fake([PayrollStatutoryCalculated::class, PayrollComplianceFailed::class]);

        [$organization, $hr, $period, $employee] = $this->statutoryScenario([
            'basic' => 10000,
            'hra' => 2000,
            'pf_eligible' => true,
            'esi_eligible' => true,
            'pan' => 'ABCDE1234F',
            'uan' => '100123456789',
            'esi_number' => '1234567890',
        ]);
        app(TenantContext::class)->set($organization);

        $run = app(PayrollCalculationService::class)->createRun($period, $hr);
        app(PayrollCalculationService::class)->calculateRun($run, $hr);

        $this->assertDatabaseHas('payroll_results', [
            'employee_id' => $employee->id,
            'payroll_run_id' => $run->id,
        ]);
        $result = PayrollResult::query()->firstOrFail();
        $this->assertArrayHasKey('statutory', $result->snapshot);
        $this->assertTrue($result->snapshot['statutory']['pf']['eligible']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'statutory_calculated']);
        Event::assertDispatched(PayrollStatutoryCalculated::class);
    }

    public function test_tds_calculation_via_payroll_engine(): void
    {
        [$organization, $hr, $period, $employee] = $this->statutoryScenario([
            'basic' => 100000,
            'hra' => 40000,
            'tax_regime' => 'new',
            'pan' => 'ABCDE1234F',
        ]);
        app(TenantContext::class)->set($organization);
        app(\App\Services\Hrms\IncomeTaxService::class)->ensureDefaultFinancialYear($hr);

        $calculation = app(PayrollCalculationService::class)->calculateEmployeePayroll($employee, $period);
        $tds = $calculation['snapshot']['statutory']['tds'];
        $this->assertSame('engine', $tds['calculation']);
        $this->assertSame('calculated', $tds['status']);
        $this->assertTrue($tds['pan_available']);
        $this->assertSame('new', $tds['tax_regime']);
        $this->assertArrayHasKey('monthly_tds', $tds);
        $this->assertSame(\App\Services\Hrms\TdsCalculationService::ENGINE_VERSION, $tds['engine_version']);
    }

    public function test_tenant_isolation_and_rbac(): void
    {
        [$organizationA, $hrA, $periodA, $employeeA] = $this->statutoryScenario();
        [$organizationB, $hrB] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organizationB);
        app(StatutoryComplianceService::class)->ensureDefaultIndiaRuleSet($hrB);

        $ruleSetA = StatutoryRuleSet::withoutGlobalScopes()
            ->where('organization_id', $organizationA->id)
            ->firstOrFail();
        $versionA = StatutoryRuleVersion::withoutGlobalScopes()
            ->where('statutory_rule_set_id', $ruleSetA->id)
            ->firstOrFail();

        $this->actingAs($hrB)->withSession(['current_organization_id' => $organizationB->id])
            ->get(route('hrms.payroll.statutory.rules.versions.show', [
                'ruleSet' => $ruleSetA->id,
                'version' => $versionA->id,
            ]))
            ->assertNotFound();

        $employeeUser = User::factory()->create();
        $organizationA->addMember($employeeUser, 'employee');

        $this->actingAs($employeeUser)->withSession(['current_organization_id' => $organizationA->id])
            ->get(route('hrms.payroll.statutory.index'))
            ->assertForbidden();

        $this->actingAs($hrA)->withSession(['current_organization_id' => $organizationA->id])
            ->get(route('hrms.payroll.statutory.index'))
            ->assertOk();
    }

    public function test_missing_profile_and_rule_set_compliance_codes(): void
    {
        [$organization, $hr, $period, $employee] = $this->payrollOnlyScenario();
        app(TenantContext::class)->set($organization);

        $calculation = app(PayrollCalculationService::class)->calculateEmployeePayroll($employee, $period);
        $codes = collect($calculation['_compliance_errors'])->pluck('code')->all();
        $this->assertContains('missing_rule_set', $codes);
        $this->assertContains('missing_statutory_profile', $codes);
        $this->assertArrayNotHasKey('statutory', $calculation['snapshot']);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{0: Organization, 1: User, 2: PayrollPeriod, 3: Employee, 4: EmployeeStatutoryProfile}
     */
    private function statutoryScenario(array $options = []): array
    {
        [$organization, $hr, $period, $employee] = $this->payrollOnlyScenario($options);
        app(TenantContext::class)->set($organization);

        $service = app(StatutoryComplianceService::class);
        $service->ensureDefaultIndiaRuleSet($hr);

        $profile = $service->upsertProfile($employee, [
            'pf_eligible' => (bool) ($options['pf_eligible'] ?? true),
            'pf_uan' => $options['uan'] ?? '100123456789',
            'esi_eligible' => (bool) ($options['esi_eligible'] ?? true),
            'esi_number' => $options['esi_number'] ?? '1234567890',
            'professional_tax_state' => $options['pt_state'] ?? 'MH',
            'tax_regime' => $options['tax_regime'] ?? 'new',
            'pan' => $options['pan'] ?? 'ABCDE1234F',
        ], $hr);

        return [$organization, $hr, $period, $employee, $profile];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{0: Organization, 1: User, 2: PayrollPeriod, 3: Employee}
     */
    private function payrollOnlyScenario(array $options = []): array
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'active',
            'joining_date' => '2026-01-01',
        ]);

        $payroll = app(PayrollService::class);
        $basic = SalaryComponent::factory()->earning()->create([
            'organization_id' => $organization->id,
            'code' => 'BASIC',
            'name' => 'Basic',
            'is_recurring' => true,
        ]);
        $hra = SalaryComponent::factory()->earning()->create([
            'organization_id' => $organization->id,
            'code' => 'HRA',
            'name' => 'HRA',
            'is_recurring' => true,
        ]);

        $structure = $payroll->createSalaryStructure([
            'name' => 'Statutory Structure',
            'effective_date' => '2026-01-01',
            'is_active' => true,
            'components' => [
                [
                    'salary_component_id' => $basic->id,
                    'calculation_type' => 'fixed',
                    'amount' => (float) ($options['basic'] ?? 10000),
                ],
                [
                    'salary_component_id' => $hra->id,
                    'calculation_type' => 'fixed',
                    'amount' => (float) ($options['hra'] ?? 5000),
                ],
            ],
        ], $hr);

        $payroll->assignSalaryStructure($employee, [
            'salary_structure_id' => $structure->id,
            'effective_from' => '2026-01-01',
            'annual_ctc' => 300000,
        ], $hr);

        app(TenantContext::class)->set($organization);
        $payroll->getOrCreateConfiguration();

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
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        return [$organization, $user];
    }
}
