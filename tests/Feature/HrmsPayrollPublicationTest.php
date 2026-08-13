<?php

namespace Tests\Feature;

use App\Events\PayrollApproved;
use App\Events\PayrollPublished;
use App\Events\PayslipEmailed;
use App\Events\PayslipGenerated;
use App\Mail\PayslipMail;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Permission;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Services\Hrms\PayrollCalculationService;
use App\Services\Hrms\PayrollPublicationService;
use App\Services\Hrms\PayrollService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ConfiguresOrganizationMail;
use Tests\Support\LocksAttendanceForPayroll;
use Tests\TestCase;

class HrmsPayrollPublicationTest extends TestCase
{
    use ConfiguresOrganizationMail;
    use LocksAttendanceForPayroll;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-07-20 09:00:00');
        Storage::fake(config('hrms.payslips.disk', 'local'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_publication_tables_exist(): void
    {
        foreach (['payroll_approvals', 'payroll_publications', 'payslips'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_publication_permissions_seeded(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();

        foreach (['payroll.approve', 'payroll.publish', 'payslip.view', 'payslip.download'] as $slug) {
            $this->assertNotNull(Permission::query()->where('slug', $slug)->first());
            $this->assertTrue($hr->hasPermission($slug, $organization));
        }

        $employeeUser = User::factory()->create();
        $organization->addMember($employeeUser, 'employee');
        $this->assertTrue($employeeUser->hasPermission('payslip.view', $organization));
        $this->assertFalse($employeeUser->hasPermission('payroll.approve', $organization));
        $this->assertFalse($employeeUser->hasPermission('payroll.publish', $organization));
    }

    public function test_approval_and_publication_state_machine(): void
    {
        Event::fake([PayrollApproved::class, PayrollPublished::class, PayslipGenerated::class]);
        Queue::fake();

        [$organization, $hr, $period, $employee, $run] = $this->calculatedRunScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.runs.publish', $run))
            ->assertRedirect();
        $this->assertSame('calculated', $run->fresh()->status);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.runs.approve', $run), [
                'approval_type' => 'hr',
                'notes' => 'Looks good',
            ])
            ->assertRedirect(route('hrms.payroll.runs.show', $run));

        $run->refresh();
        $this->assertSame('approved', $run->status);
        $this->assertDatabaseHas('payroll_approvals', [
            'payroll_run_id' => $run->id,
            'approval_type' => 'hr',
            'notes' => 'Looks good',
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payroll_approved']);
        Event::assertDispatched(PayrollApproved::class);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.runs.calculate', $run))
            ->assertRedirect();
        $this->assertSame('approved', $run->fresh()->status);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.runs.publish', $run), ['send_emails' => '0'])
            ->assertRedirect(route('hrms.payroll.runs.show', $run));

        $run->refresh();
        $this->assertSame('published', $run->status);
        $this->assertTrue($run->isPublished());
        $this->assertDatabaseHas('payroll_publications', ['payroll_run_id' => $run->id]);
        $this->assertDatabaseHas('payslips', [
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payroll_published']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payslip_generated']);
        Event::assertDispatched(PayrollPublished::class);
        Event::assertDispatched(PayslipGenerated::class);

        $payslip = Payslip::query()->firstOrFail();
        $this->assertSame($run->results()->first()->calculation_hash, $payslip->calculation_hash);
        $this->assertTrue($payslip->hasPdf());
        $this->assertTrue(Storage::disk($payslip->pdf_disk)->exists($payslip->pdf_path));

        // Published is locked — no reverse transitions
        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.payroll.runs.approve', $run))
            ->assertRedirect();
        $this->assertSame('published', $run->fresh()->status);

        $this->assertSame('locked', $period->fresh()->status);
    }

    public function test_payslip_email_queue_and_delivery(): void
    {
        Event::fake([PayslipEmailed::class]);
        Mail::fake();

        [$organization, $hr, $period, $employee, $run] = $this->calculatedRunScenario();
        app(TenantContext::class)->set($organization);
        $this->configureOrganizationMail($organization, 'log');

        $employee->update(['email' => 'worker@example.com']);

        $service = app(PayrollPublicationService::class);
        $service->approveRun($run, $hr, ['approval_type' => 'hr']);
        $publication = $service->publishRun($run->fresh(), $hr, ['send_emails' => true]);

        $this->assertGreaterThan(0, $publication->email_queued_count);

        $payslip = Payslip::query()->firstOrFail();
        $service->sendPayslipEmail($payslip, $hr);

        Mail::assertSent(PayslipMail::class, function (PayslipMail $mail) use ($payslip) {
            return $mail->payslip->is($payslip);
        });
        $this->assertNotNull($payslip->fresh()->emailed_at);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payslip_emailed']);
        Event::assertDispatched(PayslipEmailed::class);
    }

    public function test_employee_ess_access_and_download_authorization(): void
    {
        Queue::fake();
        [$organization, $hr, $period, $employee, $run] = $this->calculatedRunScenario();
        app(TenantContext::class)->set($organization);

        $user = User::factory()->create();
        $organization->addMember($user, 'employee');
        $employee->update(['user_id' => $user->id]);

        $otherUser = User::factory()->create();
        $organization->addMember($otherUser, 'employee');
        $otherEmployee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $otherUser->id,
            'status' => 'active',
        ]);

        $service = app(PayrollPublicationService::class);
        $service->approveRun($run, $hr);
        $service->publishRun($run->fresh(), $hr, ['send_emails' => false]);
        $payslip = Payslip::query()->firstOrFail();

        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($user)->withSession($session)
            ->get(route('ess.payroll.index'))
            ->assertOk()
            ->assertSee($payslip->payslip_number);

        $this->actingAs($user)->withSession($session)
            ->get(route('ess.payroll.show', $payslip))
            ->assertOk();

        $this->actingAs($user)->withSession($session)
            ->get(route('ess.payroll.download', $payslip))
            ->assertOk();
        $this->assertDatabaseHas('audit_logs', ['event' => 'payslip_downloaded']);

        $this->actingAs($otherUser)->withSession($session)
            ->get(route('ess.payroll.show', $payslip))
            ->assertForbidden();

        $this->actingAs($otherUser)->withSession($session)
            ->get(route('ess.payroll.download', $payslip))
            ->assertForbidden();
    }

    public function test_tenant_isolation_for_payslips(): void
    {
        Queue::fake();
        [$organizationA, $hrA, $periodA, $employeeA, $runA] = $this->calculatedRunScenario();
        app(TenantContext::class)->set($organizationA);
        app(PayrollPublicationService::class)->approveRun($runA, $hrA);
        app(PayrollPublicationService::class)->publishRun($runA->fresh(), $hrA, ['send_emails' => false]);
        $payslipA = Payslip::query()->firstOrFail();

        [$organizationB, $hrB] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organizationB);
        $this->assertSame(0, Payslip::query()->count());

        $this->actingAs($hrB)->withSession(['current_organization_id' => $organizationB->id])
            ->get(route('hrms.payroll.payslips.show', $payslipA))
            ->assertNotFound();
    }

    public function test_rbac_blocks_employee_from_approve_publish(): void
    {
        [$organization, $hr, $period, $employee, $run] = $this->calculatedRunScenario();
        $employeeUser = User::factory()->create();
        $organization->addMember($employeeUser, 'employee');
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($employeeUser)->withSession($session)
            ->post(route('hrms.payroll.runs.approve', $run))
            ->assertForbidden();

        $this->actingAs($employeeUser)->withSession($session)
            ->post(route('hrms.payroll.runs.publish', $run))
            ->assertForbidden();
    }

    public function test_payslip_snapshot_matches_payroll_result(): void
    {
        Queue::fake();
        [$organization, $hr, $period, $employee, $run] = $this->calculatedRunScenario();
        app(TenantContext::class)->set($organization);

        $result = $run->results()->firstOrFail();
        app(PayrollPublicationService::class)->approveRun($run, $hr);
        app(PayrollPublicationService::class)->publishRun($run->fresh(), $hr, ['send_emails' => false]);

        $payslip = Payslip::query()->firstOrFail();
        $this->assertSame($result->calculation_hash, $payslip->calculation_hash);
        $this->assertEquals((float) $result->net_salary, (float) $payslip->net_salary);
        $this->assertEquals((float) $result->gross_salary, (float) $payslip->gross_salary);
        $this->assertSame($result->snapshot['totals'] ?? null, $payslip->snapshot['totals'] ?? null);
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
            'email' => 'employee@example.com',
        ]);

        $payroll = app(PayrollService::class);
        $basic = SalaryComponent::factory()->earning()->create([
            'organization_id' => $organization->id,
            'code' => 'BASIC',
            'name' => 'Basic',
            'is_recurring' => true,
        ]);

        $structure = $payroll->createSalaryStructure([
            'name' => 'Pub Structure',
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
        app(TenantContext::class)->set($organization);
        $payroll->getOrCreateConfiguration();

        $period = PayrollPeriod::factory()->open()->create([
            'organization_id' => $organization->id,
            'name' => 'July 2026',
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
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        return [$organization, $user];
    }
}
