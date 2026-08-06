<?php

namespace Tests\Feature;

use App\Events\PayrollAdjustmentApproved;
use App\Events\PayrollPaid;
use App\Events\SalaryRevised;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\Organization;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\PayrollResult;
use App\Models\PayrollRun;
use App\Models\Permission;
use App\Models\SalaryAdvance;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Services\Hrms\PayrollAdjustmentService;
use App\Services\Hrms\PayrollCalculationService;
use App\Services\Hrms\PayrollEnterpriseDashboardService;
use App\Services\Hrms\PayrollFinanceService;
use App\Services\Hrms\PayrollPublicationService;
use App\Services\Hrms\PayrollService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Support\LocksAttendanceForPayroll;
use Tests\TestCase;

class HrmsPayrollEnterpriseEnhancementTest extends TestCase
{
    use LocksAttendanceForPayroll;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-07-20 10:00:00');
        Storage::fake(config('hrms.payslips.disk', 'local'));
        Queue::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_enterprise_tables_and_permissions_exist(): void
    {
        $this->assertTrue(Schema::hasTable('payroll_adjustments'));
        $this->assertTrue(Schema::hasColumn('payroll_configurations', 'salary_mode'));
        $this->assertTrue(Schema::hasColumn('payroll_runs', 'payment_reference'));

        [$organization, $hr] = $this->organizationWithHrUser();

        foreach (['payroll.pay', 'payroll.lock', 'payroll.adjustment.manage', 'payroll.adjustment.approve'] as $slug) {
            $this->assertNotNull(Permission::query()->where('slug', $slug)->first(), $slug);
            $this->assertTrue($hr->hasPermission($slug, $organization), $slug);
        }
    }

    public function test_loan_and_advance_recoveries_reduce_net_pay_consistently(): void
    {
        [$organization, $hr, $period, $employee] = $this->baseScenario();
        app(TenantContext::class)->set($organization);

        EmployeeLoan::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'principal_amount' => 10000,
            'outstanding_balance' => 10000,
            'monthly_recovery' => 2000,
            'status' => 'active',
            'created_by' => $hr->id,
        ]);

        SalaryAdvance::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'amount' => 3000,
            'outstanding_balance' => 3000,
            'monthly_recovery' => 500,
            'status' => 'active',
            'requested_by' => $hr->id,
        ]);

        $calc = app(PayrollCalculationService::class);
        $run = $calc->createRun($period, $hr);
        $calc->calculateRun($run, $hr);

        $result = PayrollResult::query()->where('payroll_run_id', $run->id)->firstOrFail();
        $this->assertEquals(20000.0, (float) $result->gross_salary);
        $this->assertEquals(2500.0, (float) ($result->snapshot['totals']['recovery_total'] ?? 0));
        $this->assertEquals(17500.0, (float) $result->net_salary);

        $this->assertDatabaseHas('employee_loan_recoveries', [
            'payroll_run_id' => $run->id,
            'amount' => 2000,
        ]);
        $this->assertDatabaseHas('salary_advance_recoveries', [
            'payroll_run_id' => $run->id,
            'amount' => 500,
        ]);

        $publication = app(PayrollPublicationService::class);
        $publication->approveRun($run->fresh(), $hr);
        $publication->publishRun($run->fresh(), $hr, ['send_emails' => false]);

        $payslip = $run->fresh()->payslips()->firstOrFail();
        $this->assertEquals((float) $result->net_salary, (float) $payslip->net_salary);

        $export = app(PayrollFinanceService::class)->generateBankExport($run->fresh(), $hr, 'csv');
        $this->assertEquals((float) $result->net_salary, (float) $export->total_amount);
    }

    public function test_attendance_based_salary_mode_uses_snapshot_days(): void
    {
        [$organization, $hr, $period, $employee] = $this->baseScenario();
        app(TenantContext::class)->set($organization);

        $payroll = app(PayrollService::class);
        $config = $payroll->getOrCreateConfiguration();
        $config->update(['salary_mode' => 'attendance', 'working_days_per_month' => 26]);

        $calc = app(PayrollCalculationService::class);
        $preview = $calc->previewEmployee($employee->fresh(), $period);
        $this->assertNotNull($preview['calculation']);
        $this->assertSame('attendance', $preview['calculation']['snapshot']['proration']['salary_mode']);
        $this->assertArrayHasKey('attendance_working_days', $preview['calculation']['snapshot']['proration']);
        $this->assertArrayHasKey('snapshot_id', $preview['calculation']['snapshot']['attendance']);
    }

    public function test_bonus_incentive_penalty_and_arrears_adjustments(): void
    {
        Event::fake([PayrollAdjustmentApproved::class]);

        [$organization, $hr, $period, $employee] = $this->baseScenario();
        app(TenantContext::class)->set($organization);
        $session = ['current_organization_id' => $organization->id];
        $service = app(PayrollAdjustmentService::class);

        foreach ([
            ['type' => 'bonus', 'amount' => 1000, 'direction' => 'earning'],
            ['type' => 'incentive', 'amount' => 500, 'direction' => 'earning'],
            ['type' => 'penalty', 'amount' => 250, 'direction' => 'deduction'],
            ['type' => 'arrears', 'amount' => 750, 'direction' => 'earning'],
        ] as $row) {
            $adjustment = $service->create([
                'employee_id' => $employee->id,
                'payroll_period_id' => $period->id,
                'adjustment_type' => $row['type'],
                'direction' => $row['direction'],
                'amount' => $row['amount'],
                'title' => ucfirst($row['type']).' test',
            ], $hr);
            $service->approve($adjustment, $hr);
        }

        Event::assertDispatched(PayrollAdjustmentApproved::class);

        $calc = app(PayrollCalculationService::class);
        $run = $calc->createRun($period, $hr);
        $calc->calculateRun($run, $hr);

        $result = PayrollResult::query()->where('payroll_run_id', $run->id)->firstOrFail();
        // 20000 + 1000 + 500 + 750 - 250 = 22000
        $this->assertEquals(22000.0, (float) $result->net_salary);
        $this->assertCount(4, $result->snapshot['adjustments'] ?? []);
        $this->assertSame(4, PayrollAdjustment::query()->where('status', 'applied')->count());
    }

    public function test_paid_lifecycle_and_api_pay_endpoint(): void
    {
        Event::fake([PayrollPaid::class]);

        [$organization, $hr, $period, $employee, $run] = $this->publishedScenario();
        app(TenantContext::class)->set($organization);

        $this->assertTrue($hr->hasPermission('payroll.pay', $organization), 'HR should have payroll.pay');
        $this->assertTrue($hr->can('pay', $run), 'HR should pass PayrollRunPolicy::pay');

        $paid = app(PayrollPublicationService::class)->markPaid($run->fresh(), $hr, [
            'payment_reference' => 'NEFT-12345',
            'payment_date' => '2026-07-31',
        ]);

        $this->assertSame('paid', $paid->status);
        $this->assertSame('NEFT-12345', $paid->payment_reference);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payroll_paid']);
        Event::assertDispatched(PayrollPaid::class);

        Sanctum::actingAs($hr, ['*']);
        $headers = [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ];

        $this->getJson(route('api.payroll.dashboard'), $headers)
            ->assertOk()
            ->assertJsonPath('data.paid_payroll', 1);

        $this->getJson(route('api.payroll.runs.index'), $headers)
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_salary_revision_emits_workflow_event(): void
    {
        Event::fake([SalaryRevised::class]);

        [$organization, $hr, $period, $employee] = $this->baseScenario();
        app(TenantContext::class)->set($organization);
        $payroll = app(PayrollService::class);

        $component = SalaryComponent::factory()->earning()->create([
            'organization_id' => $organization->id,
            'code' => 'BASIC2',
        ]);
        $structure = $payroll->createSalaryStructure([
            'name' => 'Revised Structure',
            'effective_date' => '2026-08-01',
            'is_active' => true,
            'components' => [[
                'salary_component_id' => $component->id,
                'calculation_type' => 'fixed',
                'amount' => 25000,
            ]],
        ], $hr);

        $payroll->assignSalaryStructure($employee, [
            'salary_structure_id' => $structure->id,
            'effective_from' => '2026-08-01',
            'annual_ctc' => 300000,
        ], $hr);

        Event::assertDispatched(SalaryRevised::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'salary_revised']);

        $history = $payroll->salaryRevisionHistory($employee);
        $this->assertGreaterThanOrEqual(2, $history->count());
    }

    public function test_dashboard_widgets_and_branch_report_export(): void
    {
        [$organization, $hr, $period, $employee, $run] = $this->publishedScenario();
        app(TenantContext::class)->set($organization);

        $widgets = app(PayrollEnterpriseDashboardService::class)->widgets();
        $this->assertArrayHasKey('pending_payroll', $widgets);
        $this->assertArrayHasKey('generated_payroll', $widgets);
        $this->assertArrayHasKey('paid_payroll', $widgets);
        $this->assertArrayHasKey('missing_salary_structure', $widgets);
        $this->assertArrayHasKey('payroll_health', $widgets);

        $finance = app(PayrollFinanceService::class);
        $branch = $finance->reportBranchSalary($run->id);
        $this->assertNotEmpty($branch);

        $export = $finance->exportReport('branch', 'csv', $run->id);
        $this->assertTrue(Storage::disk($export['disk'])->exists($export['path']));
    }

    public function test_metadata_entities_registered(): void
    {
        $entities = config('metadata.entities');
        foreach (['salary_structure', 'employee_salary_assignment', 'payroll_run', 'payroll_adjustment', 'payslip'] as $key) {
            $this->assertArrayHasKey($key, $entities);
        }

        foreach (['payroll.paid', 'salary.revised', 'payroll.adjustment.approved'] as $trigger) {
            $this->assertArrayHasKey($trigger, config('hrms.workflow_triggers'));
        }
    }

    /**
     * @return array{0: Organization, 1: User, 2: PayrollPeriod, 3: Employee}
     */
    private function baseScenario(): array
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'active',
            'joining_date' => '2026-01-01',
            'email' => 'enterprise-payroll@example.com',
        ]);

        $payroll = app(PayrollService::class);
        $basic = SalaryComponent::factory()->earning()->create([
            'organization_id' => $organization->id,
            'code' => 'BASIC',
            'name' => 'Basic',
            'is_recurring' => true,
        ]);

        $structure = $payroll->createSalaryStructure([
            'name' => 'Enterprise Structure',
            'effective_date' => '2026-01-01',
            'is_active' => true,
            'components' => [[
                'salary_component_id' => $basic->id,
                'calculation_type' => 'fixed',
                'amount' => 20000,
            ]],
        ], $hr);

        $payroll->assignSalaryStructure($employee, [
            'salary_structure_id' => $structure->id,
            'effective_from' => '2026-01-01',
            'annual_ctc' => 240000,
        ], $hr);
        $payroll->updateConfiguration([
            'payroll_frequency' => 'monthly',
            'currency' => 'INR',
            'working_days_per_month' => 26,
            'week_off_days' => ['saturday', 'sunday'],
            'overtime_handling' => 'pay',
            'rounding_policy' => 'nearest',
            'salary_mode' => 'calendar',
            'salary_credit_day' => 1,
            'auto_generate' => false,
            'reminder_days_before_credit' => 3,
        ], $hr);

        $period = PayrollPeriod::factory()->open()->create([
            'organization_id' => $organization->id,
            'name' => 'July 2026 Enterprise',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ]);

        $this->lockAttendanceForPayrollPeriod($period, $hr);

        return [$organization, $hr, $period, $employee];
    }

    /**
     * @return array{0: Organization, 1: User, 2: PayrollPeriod, 3: Employee, 4: PayrollRun}
     */
    private function publishedScenario(): array
    {
        [$organization, $hr, $period, $employee] = $this->baseScenario();
        app(TenantContext::class)->set($organization);

        $calc = app(PayrollCalculationService::class);
        $run = $calc->createRun($period, $hr);
        $calc->calculateRun($run, $hr);

        $publication = app(PayrollPublicationService::class);
        $publication->approveRun($run->fresh(), $hr);
        $publication->publishRun($run->fresh(), $hr, ['send_emails' => false]);

        return [$organization, $hr, $period, $employee, $run->fresh()];
    }

    /** @return array{0: Organization, 1: User} */
    private function organizationWithHrUser(): array
    {
        $organization = Organization::factory()->create();
        $hr = User::factory()->create();
        $organization->addMember($hr, 'hr');

        return [$organization, $hr];
    }
}
