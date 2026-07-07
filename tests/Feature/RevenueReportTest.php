<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use App\Services\RevenueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'manager'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['currency' => 'USD']);
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function asOrganization(User $user, Organization $organization): static
    {
        app(\App\Services\TenantContext::class)->set($organization);

        return $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id]);
    }

    public function test_manager_can_view_finance_reports(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('reports.finance'));

        $response->assertOk();
        $response->assertSee(__('Accounts Receivable & Revenue'));
    }

    public function test_support_user_with_finance_view_can_access_finance_reports(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('support');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('reports.finance'));

        $response->assertOk();
    }

    public function test_hr_user_without_finance_view_can_still_access_via_reports_view(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('reports.finance'));

        $response->assertOk();
    }

    public function test_employee_cannot_view_finance_reports(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('employee');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('reports.finance'));

        $response->assertForbidden();
    }

    public function test_revenue_totals_and_dashboard_metrics(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'total' => 1000,
            'amount_paid' => 400,
            'status' => 'partially_paid',
            'issue_date' => now()->subDays(10),
            'due_date' => now()->addDays(20),
            'created_by' => $user->id,
        ]);

        Payment::factory()->create([
            'organization_id' => $organization->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'amount' => 400,
            'payment_date' => now()->subDays(5),
            'recorded_by' => $user->id,
        ]);

        $service = app(RevenueService::class);
        $filters = ['date_from' => null, 'date_to' => null, 'customer_id' => null, 'salesperson_id' => null, 'status' => null];

        $this->asOrganization($user, $organization);

        $metrics = $service->dashboardMetrics($organization, $filters);

        $this->assertEquals(600.0, $metrics['outstanding_receivables']);
        $this->assertEquals(1, $metrics['outstanding_count']);
        $this->assertEquals(400.0, $metrics['total_paid']);
        $this->assertEquals(1000.0, $metrics['total_invoiced']);
    }

    public function test_aging_buckets_group_outstanding_invoices(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'total' => 100,
            'amount_paid' => 0,
            'status' => 'issued',
            'due_date' => now()->addDays(5),
            'created_by' => $user->id,
        ]);

        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'total' => 200,
            'amount_paid' => 50,
            'status' => 'partially_paid',
            'due_date' => now()->subDays(15),
            'created_by' => $user->id,
        ]);

        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'total' => 300,
            'amount_paid' => 0,
            'status' => 'cancelled',
            'due_date' => now()->subDays(100),
            'created_by' => $user->id,
        ]);

        $this->asOrganization($user, $organization);

        $aging = app(RevenueService::class)->invoiceAging($organization, []);

        $this->assertEquals(100.0, $aging['current']['total']);
        $this->assertEquals(1, $aging['current']['count']);
        $this->assertEquals(150.0, $aging['1_30']['total']);
        $this->assertEquals(1, $aging['1_30']['count']);
        $this->assertEquals(0.0, $aging['90_plus']['total']);
    }

    public function test_customer_statement_with_running_balance(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'total' => 500,
            'amount_paid' => 200,
            'status' => 'partially_paid',
            'issue_date' => now()->subDays(20),
            'created_by' => $user->id,
        ]);

        Payment::factory()->create([
            'organization_id' => $organization->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'amount' => 200,
            'payment_date' => now()->subDays(10),
            'recorded_by' => $user->id,
        ]);

        $this->asOrganization($user, $organization);

        $statement = app(RevenueService::class)->customerStatement($customer);

        $this->assertEquals(500.0, $statement['total_invoiced']);
        $this->assertEquals(200.0, $statement['total_paid']);
        $this->assertEquals(300.0, $statement['balance_due']);
        $this->assertCount(2, $statement['ledger']);
        $this->assertEquals(300.0, $statement['ledger']->last()['balance']);
    }

    public function test_organization_isolation_for_revenue_metrics(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $otherOrg = Organization::factory()->create();

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'total' => 800,
            'amount_paid' => 0,
            'status' => 'issued',
            'created_by' => $user->id,
        ]);

        Invoice::factory()->create([
            'organization_id' => $otherOrg->id,
            'customer_id' => Customer::factory()->create(['organization_id' => $otherOrg->id])->id,
            'total' => 5000,
            'amount_paid' => 0,
            'status' => 'issued',
        ]);

        $this->asOrganization($user, $organization);

        $metrics = app(RevenueService::class)->dashboardMetrics($organization, []);

        $this->assertEquals(800.0, $metrics['outstanding_receivables']);
        $this->assertEquals(800.0, $metrics['total_invoiced']);
    }

    public function test_customer_filter_limits_revenue_metrics(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customerA = Customer::factory()->create(['organization_id' => $organization->id, 'created_by' => $user->id]);
        $customerB = Customer::factory()->create(['organization_id' => $organization->id, 'created_by' => $user->id]);

        $invoiceA = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customerA->id,
            'total' => 300,
            'status' => 'issued',
            'issue_date' => now(),
            'created_by' => $user->id,
        ]);

        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customerB->id,
            'total' => 700,
            'status' => 'issued',
            'issue_date' => now(),
            'created_by' => $user->id,
        ]);

        Payment::factory()->create([
            'organization_id' => $organization->id,
            'invoice_id' => $invoiceA->id,
            'customer_id' => $customerA->id,
            'amount' => 300,
            'payment_date' => now(),
            'recorded_by' => $user->id,
        ]);

        $this->asOrganization($user, $organization);

        $filtered = app(RevenueService::class)->dashboardMetrics($organization, ['customer_id' => $customerA->id]);

        $this->assertEquals(300.0, $filtered['total_paid']);
        $this->assertEquals(300.0, $filtered['total_invoiced']);
    }

    public function test_revenue_by_month_returns_payment_totals(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create(['organization_id' => $organization->id, 'created_by' => $user->id]);
        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);

        Payment::factory()->create([
            'organization_id' => $organization->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'amount' => 250,
            'payment_date' => now()->startOfMonth()->addDays(2),
            'recorded_by' => $user->id,
        ]);

        $this->asOrganization($user, $organization);

        $byMonth = app(RevenueService::class)->revenueByMonth($organization, []);
        $currentMonth = $byMonth->firstWhere('month', now()->format('Y-m'));

        $this->assertNotNull($currentMonth);
        $this->assertEquals(250.0, $currentMonth['total']);
    }

    public function test_revenue_by_customer_and_salesperson(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $salesUser = User::factory()->create(['name' => 'Sales Rep']);
        $organization->addMember($salesUser, 'sales-executive');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'company' => 'Acme Corp',
            'created_by' => $user->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $salesUser->id,
        ]);

        Payment::factory()->create([
            'organization_id' => $organization->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'amount' => 450,
            'payment_date' => now(),
            'recorded_by' => $user->id,
        ]);

        $this->asOrganization($user, $organization);

        $service = app(RevenueService::class);
        $byCustomer = $service->revenueByCustomer($organization, []);
        $bySalesperson = $service->revenueBySalesperson($organization, []);

        $this->assertEquals(450.0, $byCustomer->first()['total']);
        $this->assertEquals('Acme Corp', $byCustomer->first()['name']);
        $this->assertEquals('Sales Rep', $bySalesperson->first()['name']);
        $this->assertEquals(450.0, $bySalesperson->first()['total']);
    }

    public function test_revenue_by_product_aggregates_line_items(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create(['organization_id' => $organization->id, 'created_by' => $user->id]);
        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'issued',
            'issue_date' => now(),
            'created_by' => $user->id,
        ]);

        InvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Consulting',
            'quantity' => 2,
            'unit_price' => 100,
            'tax_rate' => 0,
            'discount_percent' => 0,
            'line_total' => 200,
            'sort_order' => 0,
        ]);

        $this->asOrganization($user, $organization);

        $byProduct = app(RevenueService::class)->revenueByProduct($organization, []);

        $this->assertEquals(200.0, $byProduct->first()['total']);
        $this->assertEquals('Consulting', $byProduct->first()['description']);
    }

    public function test_customer_statement_shown_on_customer_page(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create(['organization_id' => $organization->id, 'created_by' => $user->id]);

        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'total' => 100,
            'status' => 'issued',
            'issue_date' => now(),
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee(__('Account Statement'));
        $response->assertSee('100.00');
    }

    public function test_manager_can_export_revenue_csv(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('reports.export.revenue'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_manager_can_export_outstanding_invoices_csv(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create(['organization_id' => $organization->id, 'created_by' => $user->id]);
        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'total' => 500,
            'amount_paid' => 0,
            'status' => 'issued',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('reports.export.outstanding'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_manager_can_export_customer_statement_csv(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create(['organization_id' => $organization->id, 'created_by' => $user->id]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.statement.export', $customer));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_employee_cannot_export_revenue_csv(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('employee');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('reports.export.revenue'));

        $response->assertForbidden();
    }

    public function test_collection_metrics_calculated_server_side(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create(['organization_id' => $organization->id, 'created_by' => $user->id]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'total' => 1000,
            'amount_paid' => 600,
            'status' => 'partially_paid',
            'issue_date' => now()->subDays(30),
            'created_by' => $user->id,
        ]);

        Payment::factory()->create([
            'organization_id' => $organization->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'amount' => 600,
            'payment_date' => now()->subDays(10),
            'recorded_by' => $user->id,
        ]);

        $this->asOrganization($user, $organization);

        $collection = app(RevenueService::class)->collectionMetrics($organization, []);

        $this->assertEquals(60.0, $collection['collection_rate']);
        $this->assertEquals(1, $collection['invoice_count']);
        $this->assertEquals(1, $collection['payment_count']);
        $this->assertNotNull($collection['average_days_to_payment']);
    }

    public function test_chart_datasets_return_arrays_only(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $this->asOrganization($user, $organization);

        $charts = app(RevenueService::class)->chartDatasets($organization, []);

        $this->assertIsArray($charts);
        $this->assertArrayHasKey('aging', $charts);
        $this->assertArrayHasKey('labels', $charts['aging']);
        $this->assertArrayHasKey('totals', $charts['aging']);
        $this->assertIsArray($charts['revenue_by_month']['labels']);
        $this->assertIsArray($charts['revenue_by_month']['totals']);
    }
}
