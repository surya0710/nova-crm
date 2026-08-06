<?php

namespace Tests\Feature;

use App\Events\TaxDeclarationApproved;
use App\Events\TaxDeclarationRejected;
use App\Events\TaxDeclarationSubmitted;
use App\Events\TaxProofUploaded;
use App\Events\TaxProofVerified;
use App\Events\TdsCalculated;
use App\Models\Employee;
use App\Models\Form16Record;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\SalaryComponent;
use App\Models\TaxDeclaration;
use App\Models\TaxFinancialYear;
use App\Models\TaxProof;
use App\Models\TaxProjection;
use App\Models\TdsMonthlyCalculation;
use App\Models\User;
use App\Services\Hrms\IncomeTaxService;
use App\Services\Hrms\InvestmentDeclarationService;
use App\Services\Hrms\PayrollCalculationService;
use App\Services\Hrms\PayrollService;
use App\Services\Hrms\StatutoryComplianceService;
use App\Services\Hrms\TaxDashboardService;
use App\Services\Hrms\TaxFacadeService;
use App\Services\Hrms\TaxProjectionService;
use App\Services\Hrms\TdsCalculationService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Support\LocksAttendanceForPayroll;
use Tests\TestCase;

class HrmsIncomeTaxTdsTest extends TestCase
{
    use LocksAttendanceForPayroll;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-07-20 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_schema_and_permissions_exist(): void
    {
        foreach ([
            'tax_financial_years',
            'tax_slabs',
            'employee_tax_regimes',
            'tax_projections',
            'tax_declarations',
            'tax_declaration_items',
            'tax_proofs',
            'tax_proof_audits',
            'tds_monthly_calculations',
            'form16_records',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table {$table}");
        }

        foreach (['tax.view', 'tax.manage', 'tax.verify', 'tax.calculate', 'form16.generate'] as $slug) {
            $this->assertTrue(Permission::query()->where('slug', $slug)->exists(), "Missing permission {$slug}");
        }

        foreach ([
            'tax.declaration.submitted',
            'tax.declaration.approved',
            'tax.declaration.rejected',
            'tax.proof.uploaded',
            'tax.proof.verified',
            'tds.calculated',
        ] as $trigger) {
            $this->assertArrayHasKey($trigger, config('hrms.workflow_triggers'));
        }

        foreach (['tax_declaration', 'tax_proof', 'tax_projection', 'tax_regime', 'tax_financial_year'] as $entity) {
            $this->assertArrayHasKey($entity, config('metadata.entities'));
        }
    }

    public function test_income_tax_slab_calculation_new_regime_with_rebate(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $fy = app(IncomeTaxService::class)->ensureDefaultFinancialYear($hr);
        $tax = app(IncomeTaxService::class)->calculateAnnualTax(600000, 'new', $fy);

        // New regime: 0–3L @0, 3–6L @5% = 15,000; rebate 87A wipes tax <= 7L
        $this->assertSame(0.0, (float) $tax['total_tax']);
        $this->assertGreaterThan(0, (float) $tax['rebate']);
        $this->assertSame('new', $tax['regime']);
        $this->assertSame(IncomeTaxService::ENGINE_VERSION, $tax['engine_version']);
    }

    public function test_income_tax_slab_calculation_old_regime(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $fy = app(IncomeTaxService::class)->ensureDefaultFinancialYear($hr);
        // Taxable 8L old: 0–2.5L 0, 2.5–5L 5%=12500, 5–8L 20%=60000 => 72500 + 4% cess
        $tax = app(IncomeTaxService::class)->calculateAnnualTax(800000, 'old', $fy);

        $this->assertSame(72500.0, (float) $tax['tax_after_rebate']);
        $this->assertEqualsWithDelta(2900.0, (float) $tax['cess'], 0.01);
        $this->assertEqualsWithDelta(75400.0, (float) $tax['total_tax'], 0.01);
    }

    public function test_financial_year_setup_and_regime_selection(): void
    {
        [$organization, $hr, $period, $employee] = $this->taxScenario();
        app(TenantContext::class)->set($organization);

        $facade = app(TaxFacadeService::class);
        $fy = $facade->ensureFinancialYear($hr);
        $this->assertTrue($fy->is_active);
        $this->assertGreaterThan(0, $fy->slabs()->count());

        $regime = $facade->selectRegime($employee, $fy, [
            'regime' => 'old',
            'effective_from' => $fy->start_date->toDateString(),
        ], $hr);

        $this->assertSame('old', $regime->regime);
        $this->assertSame('old', app(IncomeTaxService::class)->resolveEmployeeRegime($employee->fresh(), $fy));
        $this->assertDatabaseHas('audit_logs', ['event' => 'tax_regime_changed']);
    }

    public function test_declaration_workflow_and_events(): void
    {
        Event::fake([
            TaxDeclarationSubmitted::class,
            TaxDeclarationApproved::class,
            TaxDeclarationRejected::class,
        ]);

        [$organization, $hr, $period, $employee] = $this->taxScenario();
        app(TenantContext::class)->set($organization);
        $fy = app(IncomeTaxService::class)->ensureDefaultFinancialYear($hr);

        $facade = app(TaxFacadeService::class);
        $declaration = $facade->createDeclaration($employee, $fy, [
            [
                'category' => '80C',
                'section' => '80C',
                'label' => 'ELSS',
                'declared_amount' => 100000,
            ],
            [
                'category' => '80D',
                'section' => '80D',
                'label' => 'Health Insurance',
                'declared_amount' => 25000,
            ],
        ], $hr);

        $this->assertSame(TaxDeclaration::STATUS_DRAFT, $declaration->status);
        $this->assertSame(125000.0, (float) $declaration->declared_total);

        $submitted = $facade->submitDeclaration($declaration, $hr);
        $this->assertSame(TaxDeclaration::STATUS_SUBMITTED, $submitted->status);
        Event::assertDispatched(TaxDeclarationSubmitted::class);

        $verified = $facade->verifyDeclaration($submitted, $hr, 'Looks good');
        $this->assertSame(TaxDeclaration::STATUS_VERIFIED, $verified->status);
        $this->assertSame(125000.0, (float) $verified->approved_total);
        Event::assertDispatched(TaxDeclarationApproved::class);

        $other = $facade->createDeclaration($employee, $fy, [
            ['category' => '80C', 'section' => '80C', 'label' => 'PF', 'declared_amount' => 50000],
        ], $hr);
        $facade->submitDeclaration($other, $hr);
        $rejected = $facade->rejectDeclaration($other->fresh(), 'Incomplete proofs', $hr);
        $this->assertSame(TaxDeclaration::STATUS_REJECTED, $rejected->status);
        Event::assertDispatched(TaxDeclarationRejected::class);
    }

    public function test_proof_verification_workflow(): void
    {
        Event::fake([TaxProofUploaded::class, TaxProofVerified::class]);
        Storage::fake(config('hrms.payslips.disk', 'local'));

        [$organization, $hr, $period, $employee] = $this->taxScenario();
        app(TenantContext::class)->set($organization);
        $fy = app(IncomeTaxService::class)->ensureDefaultFinancialYear($hr);
        $facade = app(TaxFacadeService::class);

        $declaration = $facade->createDeclaration($employee, $fy, [
            ['category' => '80C', 'section' => '80C', 'label' => 'ELSS', 'declared_amount' => 50000],
        ], $hr);
        $facade->submitDeclaration($declaration, $hr);

        $proof = $facade->uploadProof($declaration->fresh(), [
            'category' => '80C',
            'title' => 'ELSS Statement',
            'claimed_amount' => 50000,
            'tax_declaration_item_id' => $declaration->items()->first()->id,
        ], UploadedFile::fake()->create('elss.pdf', 100, 'application/pdf'), $hr);

        $this->assertSame(TaxProof::STATUS_UPLOADED, $proof->status);
        Event::assertDispatched(TaxProofUploaded::class);

        $partial = $facade->verifyProof($proof, 30000, 'Partial approval', $hr);
        $this->assertSame(TaxProof::STATUS_PARTIAL, $partial->status);
        $this->assertSame(30000.0, (float) $partial->approved_amount);
        $this->assertDatabaseHas('tax_proof_audits', [
            'tax_proof_id' => $proof->id,
        ]);
        Event::assertDispatched(TaxProofVerified::class);
    }

    public function test_tax_projection_and_monthly_tds(): void
    {
        Event::fake([TdsCalculated::class]);

        [$organization, $hr, $period, $employee] = $this->taxScenario([
            'basic' => 100000,
            'hra' => 40000,
            'tax_regime' => 'new',
        ]);
        app(TenantContext::class)->set($organization);

        $fy = app(IncomeTaxService::class)->ensureDefaultFinancialYear($hr);
        $projection = app(TaxProjectionService::class)->projectForEmployee(
            $employee,
            $fy,
            $period,
            140000,
            'nearest',
            $hr
        );

        $this->assertInstanceOf(TaxProjection::class, $projection);
        $this->assertGreaterThan(0, (float) $projection->projected_gross);
        $this->assertSame('new', $projection->regime);

        $profile = app(StatutoryComplianceService::class)->getProfileForEmployee($employee);
        $tds = app(TdsCalculationService::class)->calculateForPayroll(
            $employee,
            $period,
            $profile,
            ['gross_salary' => 140000, 'snapshot' => ['earnings' => []]],
            config('hrms.statutory.default_india_configuration.tds'),
            'nearest',
            $hr
        );

        $this->assertSame('engine', $tds['calculation']);
        $this->assertSame('calculated', $tds['status']);
        $this->assertArrayHasKey('monthly_tds', $tds);
        $this->assertDatabaseHas('tds_monthly_calculations', [
            'employee_id' => $employee->id,
            'month' => 7,
            'year' => 2026,
        ]);
        Event::assertDispatched(TdsCalculated::class);
    }

    public function test_payroll_integration_consumes_tds_engine(): void
    {
        Event::fake([TdsCalculated::class]);

        [$organization, $hr, $period, $employee] = $this->taxScenario([
            'basic' => 80000,
            'hra' => 32000,
            'tax_regime' => 'new',
            'pan' => 'ABCDE1234F',
        ]);
        app(TenantContext::class)->set($organization);
        app(IncomeTaxService::class)->ensureDefaultFinancialYear($hr);

        $calculation = app(PayrollCalculationService::class)->calculateEmployeePayroll($employee, $period);
        $tds = $calculation['snapshot']['statutory']['tds'];

        $this->assertSame('engine', $tds['calculation']);
        $this->assertNotSame('deferred', $tds['calculation']);
        $this->assertSame(TdsCalculationService::ENGINE_VERSION, $tds['engine_version']);
        $this->assertSame(StatutoryComplianceService::ENGINE_VERSION, $calculation['snapshot']['engine_version']);

        $tdsLine = collect($calculation['snapshot']['deductions'])->firstWhere('code', 'TDS');
        $this->assertNotNull($tdsLine);
        $this->assertSame('Income Tax (TDS)', $tdsLine['name']);
        $this->assertSame((float) $tds['amount'], (float) $tdsLine['amount']);
    }

    public function test_form16_foundation_and_dashboard_reports_api_rbac(): void
    {
        [$organization, $hr, $period, $employee] = $this->taxScenario([
            'basic' => 50000,
            'hra' => 20000,
        ]);
        app(TenantContext::class)->set($organization);
        $fy = app(IncomeTaxService::class)->ensureDefaultFinancialYear($hr);
        $facade = app(TaxFacadeService::class);

        $facade->projectEmployee($employee, $fy, $period, 70000, $hr);
        $form16 = $facade->generateForm16($employee, $fy, $hr);
        $this->assertInstanceOf(Form16Record::class, $form16);
        $this->assertNotEmpty($form16->part_a);
        $this->assertNotEmpty($form16->employee_details);
        $this->assertDatabaseHas('audit_logs', ['event' => 'form16_generated']);

        $widgets = app(TaxDashboardService::class)->widgets();
        $this->assertArrayHasKey('pending_declarations', $widgets);
        $this->assertArrayHasKey('monthly_tds', $widgets);
        $this->assertArrayHasKey('annual_tax_liability', $widgets);
        $this->assertArrayHasKey('employees_without_regime', $widgets);

        $this->assertTrue($hr->hasPermission('tax.view', $organization));
        $this->assertTrue($hr->hasPermission('form16.generate', $organization));

        Sanctum::actingAs($hr, ['*']);
        $this->getJson(route('api.tax.dashboard'), [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ])
            ->assertOk()
            ->assertJsonStructure(['data']);

        $employeeUser = User::factory()->create();
        $organization->addMember($employeeUser, 'employee');
        $this->assertFalse($employeeUser->hasPermission('tax.view', $organization));

        Sanctum::actingAs($employeeUser, ['*']);
        $this->getJson(route('api.tax.dashboard'), [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ])->assertForbidden();
    }

    public function test_declaration_service_unit_path(): void
    {
        [$organization, $hr, $period, $employee] = $this->taxScenario();
        app(TenantContext::class)->set($organization);
        $fy = app(IncomeTaxService::class)->ensureDefaultFinancialYear($hr);

        $service = app(InvestmentDeclarationService::class);
        $declaration = $service->createDraft($employee, $fy, [
            ['category' => 'hra', 'section' => '10(13A)', 'label' => 'HRA', 'declared_amount' => 120000],
        ], $hr);

        $this->assertTrue($declaration->canSubmit());
        $list = $service->listForOrganization('draft');
        $this->assertTrue($list->contains('id', $declaration->id));
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{0: Organization, 1: User, 2: \App\Models\PayrollPeriod, 3: Employee}
     */
    private function taxScenario(array $options = []): array
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
            'is_taxable' => true,
        ]);
        $hra = SalaryComponent::factory()->earning()->create([
            'organization_id' => $organization->id,
            'code' => 'HRA',
            'name' => 'HRA',
            'is_recurring' => true,
            'is_taxable' => true,
        ]);

        $structure = $payroll->createSalaryStructure([
            'name' => 'Tax Structure',
            'effective_date' => '2026-01-01',
            'is_active' => true,
            'components' => [
                [
                    'salary_component_id' => $basic->id,
                    'calculation_type' => 'fixed',
                    'amount' => (float) ($options['basic'] ?? 50000),
                ],
                [
                    'salary_component_id' => $hra->id,
                    'calculation_type' => 'fixed',
                    'amount' => (float) ($options['hra'] ?? 20000),
                ],
            ],
        ], $hr);

        $payroll->assignSalaryStructure($employee, [
            'salary_structure_id' => $structure->id,
            'effective_from' => '2026-01-01',
            'annual_ctc' => (($options['basic'] ?? 50000) + ($options['hra'] ?? 20000)) * 12,
        ], $hr);

        app(TenantContext::class)->set($organization);
        $payroll->getOrCreateConfiguration();

        $period = \App\Models\PayrollPeriod::factory()->open()->create([
            'organization_id' => $organization->id,
            'name' => 'July 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ]);
        $this->lockAttendanceForPayrollPeriod($period, $hr);

        app(TenantContext::class)->set($organization);
        $statutory = app(StatutoryComplianceService::class);
        $statutory->ensureDefaultIndiaRuleSet($hr);
        $statutory->upsertProfile($employee, [
            'pf_eligible' => true,
            'pf_uan' => '100123456789',
            'esi_eligible' => false,
            'professional_tax_state' => 'MH',
            'tax_regime' => $options['tax_regime'] ?? 'new',
            'pan' => $options['pan'] ?? 'ABCDE1234F',
        ], $hr);

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
