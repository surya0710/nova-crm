<?php

namespace Tests\Feature;

use App\Events\EmployeeLoanClosed;
use App\Events\EmployeeLoanCreated;
use App\Events\EmployeeSettlementCompleted;
use App\Events\PayrollBankExported;
use App\Events\PayrollLedgerGenerated;
use App\Events\PayrollReversed;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeLoan;
use App\Models\EmployeeSettlement;
use App\Models\ExpenseReimbursement;
use App\Models\Organization;
use App\Models\PayrollBankExport;
use App\Models\PayrollJournal;
use App\Models\PayrollLedgerEntry;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Permission;
use App\Models\SalaryAdvance;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Services\Hrms\PayrollCalculationService;
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
use Tests\Support\LocksAttendanceForPayroll;
use Tests\TestCase;

class HrmsPayrollFinanceTest extends TestCase
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

    public function test_finance_tables_exist(): void
    {
        foreach ([
            'payroll_ledger_entries',
            'payroll_journals',
            'payroll_journal_lines',
            'payroll_bank_exports',
            'employee_loans',
            'employee_loan_recoveries',
            'salary_advances',
            'salary_advance_recoveries',
            'expense_reimbursements',
            'employee_settlements',
            'payroll_reversals',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_finance_permissions_seeded_and_employee_denied(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();

        foreach ([
            'payroll.finance.view',
            'payroll.finance.manage',
            'payroll.bank.export',
            'payroll.settlement.manage',
            'payroll.loan.manage',
        ] as $slug) {
            $this->assertNotNull(Permission::query()->where('slug', $slug)->first());
            $this->assertTrue($hr->hasPermission($slug, $organization));
        }

        $employeeUser = User::factory()->create();
        $organization->addMember($employeeUser, 'employee');

        $this->assertFalse($employeeUser->hasPermission('payroll.finance.view', $organization));
        $this->assertFalse($employeeUser->hasPermission('payroll.loan.manage', $organization));
    }

    public function test_ledger_generation_creates_balanced_journal(): void
    {
        Event::fake([PayrollLedgerGenerated::class]);

        [$organization, $hr, $period, $employee, $run] = $this->publishedRunScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.ledger.generate'), [
                'payroll_run_id' => $run->id,
            ])
            ->assertRedirect();

        $entries = PayrollLedgerEntry::query()->where('payroll_run_id', $run->id)->get();
        $this->assertGreaterThan(0, $entries->count());

        $debit = round((float) $entries->where('entry_type', 'debit')->sum('amount'), 2);
        $credit = round((float) $entries->where('entry_type', 'credit')->sum('amount'), 2);
        $this->assertEquals($debit, $credit);

        $journal = PayrollJournal::query()->where('payroll_run_id', $run->id)->firstOrFail();
        $this->assertTrue($journal->isBalanced());
        $this->assertGreaterThan(0, $journal->lines()->count());

        $this->assertDatabaseHas('audit_logs', ['event' => 'payroll_ledger_generated']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payroll_journal_generated']);
        Event::assertDispatched(PayrollLedgerGenerated::class);

        // Immutable — second generate fails
        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.ledger.generate'), [
                'payroll_run_id' => $run->id,
            ])
            ->assertRedirect();
        $this->assertSame($entries->count(), PayrollLedgerEntry::query()->where('payroll_run_id', $run->id)->count());
    }

    public function test_bank_export_csv_and_xlsx(): void
    {
        Event::fake([PayrollBankExported::class]);

        [$organization, $hr, $period, $employee, $run] = $this->publishedRunScenario();
        app(TenantContext::class)->set($organization);

        EmployeeBankAccount::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'bank_name' => 'HDFC',
            'account_holder_name' => $employee->full_name,
            'account_number' => '1234567890',
            'ifsc_or_swift' => 'HDFC0001234',
            'is_primary' => true,
        ]);

        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.bank-exports.store'), [
                'payroll_run_id' => $run->id,
                'format' => 'csv',
            ])
            ->assertRedirect();

        $csv = PayrollBankExport::query()->where('format', 'csv')->firstOrFail();
        $this->assertTrue($csv->fileExists());
        $this->assertGreaterThan(0, (float) $csv->total_amount);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payroll_bank_exported']);
        Event::assertDispatched(PayrollBankExported::class);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.bank-exports.store'), [
                'payroll_run_id' => $run->id,
                'format' => 'xlsx',
            ])
            ->assertRedirect();

        $xlsx = PayrollBankExport::query()->where('format', 'xlsx')->firstOrFail();
        $this->assertTrue($xlsx->fileExists());

        $this->actingAs($hr)->withSession($session)
            ->get(route('hrms.payroll.bank-exports.download', $csv))
            ->assertOk();
    }

    public function test_loan_lifecycle_and_payroll_recovery(): void
    {
        Event::fake([EmployeeLoanCreated::class, EmployeeLoanClosed::class, PayrollLedgerGenerated::class]);

        [$organization, $hr, $period, $employee, $run] = $this->publishedRunScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.loans.store'), [
                'employee_id' => $employee->id,
                'principal_amount' => 10000,
                'monthly_recovery' => 2500,
                'loan_type' => 'general',
            ])
            ->assertRedirect();

        $loan = EmployeeLoan::query()->firstOrFail();
        $this->assertSame('active', $loan->status);
        $this->assertEquals(10000.0, (float) $loan->outstanding_balance);
        $this->assertDatabaseHas('audit_logs', ['event' => 'employee_loan_created']);
        Event::assertDispatched(EmployeeLoanCreated::class);

        app(TenantContext::class)->set($organization);
        app(PayrollFinanceService::class)->generateLedgerForRun($run->fresh(), $hr);

        $loan->refresh();
        $this->assertEquals(7500.0, (float) $loan->outstanding_balance);
        $this->assertDatabaseHas('employee_loan_recoveries', [
            'employee_loan_id' => $loan->id,
            'payroll_run_id' => $run->id,
            'amount' => 2500,
        ]);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.loans.close', $loan), [
                'reason' => 'Early payoff',
            ])
            ->assertRedirect();

        $loan->refresh();
        $this->assertSame('closed', $loan->status);
        $this->assertEquals(0.0, (float) $loan->outstanding_balance);
        $this->assertDatabaseHas('audit_logs', ['event' => 'employee_loan_closed']);
        Event::assertDispatched(EmployeeLoanClosed::class);
    }

    public function test_advance_approval_and_recovery(): void
    {
        [$organization, $hr, $period, $employee, $run] = $this->publishedRunScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.advances.store'), [
                'employee_id' => $employee->id,
                'amount' => 5000,
                'monthly_recovery' => 1000,
                'reason' => 'Emergency',
            ])
            ->assertRedirect();

        $advance = SalaryAdvance::query()->firstOrFail();
        $this->assertSame('pending', $advance->status);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.advances.approve', $advance))
            ->assertRedirect();

        $advance->refresh();
        $this->assertSame('active', $advance->status);

        app(TenantContext::class)->set($organization);
        app(PayrollFinanceService::class)->generateLedgerForRun($run->fresh(), $hr);

        $advance->refresh();
        $this->assertEquals(4000.0, (float) $advance->outstanding_balance);
        $this->assertDatabaseHas('salary_advance_recoveries', [
            'salary_advance_id' => $advance->id,
            'payroll_run_id' => $run->id,
        ]);
    }

    public function test_reimbursement_approval_and_inclusion(): void
    {
        [$organization, $hr, $period, $employee, $run] = $this->publishedRunScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.reimbursements.store'), [
                'employee_id' => $employee->id,
                'amount' => 1500,
                'category' => 'travel',
                'is_taxable' => false,
                'description' => 'Client travel',
            ])
            ->assertRedirect();

        $claim = ExpenseReimbursement::query()->firstOrFail();
        $this->assertSame('pending', $claim->status);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.reimbursements.approve', $claim))
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', ['event' => 'reimbursement_approved']);

        app(TenantContext::class)->set($organization);
        app(PayrollFinanceService::class)->generateLedgerForRun($run->fresh(), $hr);

        $claim->refresh();
        $this->assertSame('included', $claim->status);
        $this->assertSame($run->id, $claim->payroll_run_id);
        $this->assertDatabaseHas('payroll_ledger_entries', [
            'payroll_run_id' => $run->id,
            'account_code' => 'REIMB_PAY',
        ]);
    }

    public function test_final_settlement(): void
    {
        Event::fake([EmployeeSettlementCompleted::class, EmployeeLoanClosed::class]);

        [$organization, $hr, $period, $employee, $run] = $this->publishedRunScenario();
        app(TenantContext::class)->set($organization);

        app(PayrollFinanceService::class)->createLoan([
            'employee_id' => $employee->id,
            'principal_amount' => 3000,
            'monthly_recovery' => 500,
        ], $hr);

        $claim = app(PayrollFinanceService::class)->createReimbursement([
            'employee_id' => $employee->id,
            'amount' => 800,
            'is_taxable' => false,
        ], $hr);
        app(PayrollFinanceService::class)->approveReimbursement($claim, $hr);

        $session = ['current_organization_id' => $organization->id];
        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.settlements.store'), [
                'employee_id' => $employee->id,
                'pending_salary' => 10000,
                'leave_encashment' => 2000,
                'asset_deductions' => 0,
                'statutory_deductions' => 500,
                'notes' => 'Exit settlement',
            ])
            ->assertRedirect();

        $settlement = EmployeeSettlement::query()->firstOrFail();
        $this->assertSame('completed', $settlement->status);
        $this->assertEquals(3000.0, (float) $settlement->loan_recovery);
        $this->assertEquals(800.0, (float) $settlement->reimbursements);
        // 10000 + 2000 + 800 - 3000 - 0 - 0 - 500 = 9300
        $this->assertEquals(9300.0, (float) $settlement->net_settlement);
        $this->assertNotEmpty($settlement->statement);
        $this->assertDatabaseHas('audit_logs', ['event' => 'employee_settlement_completed']);
        Event::assertDispatched(EmployeeSettlementCompleted::class);

        $this->assertSame('closed', EmployeeLoan::query()->first()->status);
    }

    public function test_payroll_reversal_creates_reversing_entries(): void
    {
        Event::fake([PayrollReversed::class, PayrollLedgerGenerated::class]);

        [$organization, $hr, $period, $employee, $run] = $this->publishedRunScenario();
        app(TenantContext::class)->set($organization);

        $finance = app(PayrollFinanceService::class);
        $finance->generateLedgerForRun($run->fresh(), $hr);
        $originalCount = PayrollLedgerEntry::query()->where('is_reversal', false)->count();

        $session = ['current_organization_id' => $organization->id];
        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.runs.reverse', $run), [
                'reason' => 'Incorrect bank details discovered after publish',
            ])
            ->assertRedirect();

        $run->refresh();
        $this->assertSame('reversed', $run->status);
        $this->assertTrue($run->isReversed());
        $this->assertDatabaseHas('payroll_reversals', [
            'payroll_run_id' => $run->id,
            'reason' => 'Incorrect bank details discovered after publish',
        ]);

        $reversing = PayrollLedgerEntry::query()->where('is_reversal', true)->get();
        $this->assertSame($originalCount, $reversing->count());

        $journal = PayrollJournal::query()->where('is_reversal', true)->firstOrFail();
        $this->assertTrue($journal->isBalanced());

        // Payslips still exist — never deleted
        $this->assertGreaterThan(0, $run->payslips()->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'payroll_reversed']);
        Event::assertDispatched(PayrollReversed::class);
    }

    public function test_financial_reports_readable(): void
    {
        [$organization, $hr, $period, $employee, $run] = $this->publishedRunScenario();
        app(TenantContext::class)->set($organization);
        app(PayrollFinanceService::class)->generateLedgerForRun($run->fresh(), $hr);

        $session = ['current_organization_id' => $organization->id];

        foreach (['summary', 'statutory', 'salary_register', 'department', 'cost_center', 'ledger'] as $report) {
            $this->actingAs($hr)->withSession($session)
                ->get(route('hrms.payroll.reports.index', [
                    'report' => $report,
                    'payroll_run_id' => $run->id,
                ]))
                ->assertOk();
        }

        $summary = app(PayrollFinanceService::class)->reportPayrollSummary($run->id);
        $this->assertSame(1, $summary['run_count']);
        $this->assertGreaterThan(0, $summary['net_salary']);
    }

    public function test_rbac_blocks_employee_from_finance(): void
    {
        [$organization, $hr, $period, $employee, $run] = $this->publishedRunScenario();
        $employeeUser = User::factory()->create();
        $organization->addMember($employeeUser, 'employee');
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($employeeUser)->withSession($session)
            ->get(route('hrms.payroll.ledger.index'))
            ->assertForbidden();

        $this->actingAs($employeeUser)->withSession($session)
            ->post(route('hrms.payroll.ledger.generate'), ['payroll_run_id' => $run->id])
            ->assertForbidden();

        $this->actingAs($employeeUser)->withSession($session)
            ->post(route('hrms.payroll.bank-exports.store'), [
                'payroll_run_id' => $run->id,
                'format' => 'csv',
            ])
            ->assertForbidden();

        $this->actingAs($employeeUser)->withSession($session)
            ->post(route('hrms.payroll.runs.reverse', $run), [
                'reason' => 'Should not work',
            ])
            ->assertForbidden();
    }

    public function test_tenant_isolation_for_finance_resources(): void
    {
        [$organizationA, $hrA, $periodA, $employeeA, $runA] = $this->publishedRunScenario();
        app(TenantContext::class)->set($organizationA);
        app(PayrollFinanceService::class)->generateLedgerForRun($runA->fresh(), $hrA);
        $journalA = PayrollJournal::query()->firstOrFail();

        [$organizationB, $hrB] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organizationB);

        $this->assertSame(0, PayrollLedgerEntry::query()->count());
        $this->assertSame(0, PayrollJournal::query()->count());

        $this->actingAs($hrB)->withSession(['current_organization_id' => $organizationB->id])
            ->get(route('hrms.payroll.journals.show', $journalA))
            ->assertNotFound();
    }

    public function test_workflow_events_have_expected_triggers(): void
    {
        $this->assertSame('payroll.ledger.generated', (new PayrollLedgerGenerated(1, 'x', 1, []))->trigger());
        $this->assertSame('payroll.bank.exported', (new PayrollBankExported(1, 'x', 1, []))->trigger());
        $this->assertSame('employee.loan.created', (new EmployeeLoanCreated(1, 'x', 1, []))->trigger());
        $this->assertSame('employee.loan.closed', (new EmployeeLoanClosed(1, 'x', 1, []))->trigger());
        $this->assertSame('employee.settlement.completed', (new EmployeeSettlementCompleted(1, 'x', 1, []))->trigger());
        $this->assertSame('payroll.reversed', (new PayrollReversed(1, 'x', 1, []))->trigger());

        foreach ([
            'payroll.ledger.generated',
            'payroll.bank.exported',
            'employee.loan.created',
            'employee.loan.closed',
            'employee.settlement.completed',
            'payroll.reversed',
        ] as $trigger) {
            $this->assertArrayHasKey($trigger, config('hrms.workflow_triggers'));
        }
    }

    /**
     * @return array{0: Organization, 1: User, 2: PayrollPeriod, 3: Employee, 4: PayrollRun}
     */
    private function publishedRunScenario(): array
    {
        [$organization, $hr, $period, $employee, $run] = $this->calculatedRunScenario();
        app(TenantContext::class)->set($organization);

        $publication = app(PayrollPublicationService::class);
        $publication->approveRun($run, $hr);
        $publication->publishRun($run->fresh(), $hr, ['send_emails' => false]);

        return [$organization, $hr, $period, $employee, $run->fresh()];
    }

    /**
     * @return array{0: Organization, 1: User, 2: PayrollPeriod, 3: Employee, 4: PayrollRun}
     */
    private function calculatedRunScenario(): array
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'active',
            'joining_date' => '2026-01-01',
            'email' => 'finance-employee@example.com',
        ]);

        $payroll = app(PayrollService::class);
        $basic = SalaryComponent::factory()->earning()->create([
            'organization_id' => $organization->id,
            'code' => 'BASIC',
            'name' => 'Basic',
            'is_recurring' => true,
        ]);

        $structure = $payroll->createSalaryStructure([
            'name' => 'Finance Structure',
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
        $payroll->getOrCreateConfiguration();

        $period = PayrollPeriod::factory()->open()->create([
            'organization_id' => $organization->id,
            'name' => 'July 2026 Finance',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ]);

        $this->lockAttendanceForPayrollPeriod($period, $hr);

        $calc = app(PayrollCalculationService::class);
        $run = $calc->createRun($period, $hr);
        $calc->calculateRun($run, $hr);

        return [$organization, $hr, $period, $employee, $run->fresh()];
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
