<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'manager'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function createIssuedInvoice(Organization $organization, User $user, float $total = 1000): Invoice
    {
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        return Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'total' => $total,
            'subtotal' => $total,
            'amount_paid' => 0,
            'status' => 'issued',
            'created_by' => $user->id,
        ]);
    }

    protected function recordPayment(User $user, Organization $organization, Invoice $invoice, float $amount, array $overrides = [])
    {
        return $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('payments.store'), array_merge([
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'payment_date' => now()->toDateString(),
                'method' => 'bank_transfer',
            ], $overrides));
    }

    public function test_manager_can_view_payments_index(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('payments.index'));

        $response->assertOk();
    }

    public function test_hr_user_cannot_access_payments(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('payments.index'));

        $response->assertForbidden();
    }

    public function test_manager_can_record_payment(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $invoice = $this->createIssuedInvoice($organization, $user);

        $response = $this->recordPayment($user, $organization, $invoice, 400, [
            'reference' => 'TXN-123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => 400,
            'reference' => 'TXN-123',
            'recorded_by' => $user->id,
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'amount_paid' => 400,
            'status' => 'partially_paid',
        ]);
    }

    public function test_partial_payment_updates_balance(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $invoice = $this->createIssuedInvoice($organization, $user, 1000);

        $this->recordPayment($user, $organization, $invoice, 300)->assertRedirect();

        $invoice->refresh();
        $this->assertSame(300.0, (float) $invoice->amount_paid);
        $this->assertSame(700.0, $invoice->balance_due);
        $this->assertSame('partially_paid', $invoice->status);
    }

    public function test_multiple_payments_eventually_pay_invoice(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $invoice = $this->createIssuedInvoice($organization, $user, 1000);

        $this->recordPayment($user, $organization, $invoice, 300)->assertRedirect();
        $this->recordPayment($user, $organization, $invoice, 700)->assertRedirect();

        $invoice->refresh();
        $this->assertSame(1000.0, (float) $invoice->amount_paid);
        $this->assertSame(0.0, $invoice->balance_due);
        $this->assertSame('paid', $invoice->status);
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_invoice_becomes_paid_when_fully_paid(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $invoice = $this->createIssuedInvoice($organization, $user, 500);

        $this->recordPayment($user, $organization, $invoice, 500)->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'amount_paid' => 500,
            'status' => 'paid',
        ]);
    }

    public function test_payment_cannot_exceed_invoice_balance(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $invoice = $this->createIssuedInvoice($organization, $user, 500);

        $response = $this->recordPayment($user, $organization, $invoice, 600);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_draft_invoice_payment_is_rejected(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $invoice = $this->createIssuedInvoice($organization, $user);
        $invoice->update(['status' => 'draft']);

        $response = $this->recordPayment($user, $organization, $invoice, 100);

        $response->assertSessionHasErrors('invoice_id');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_cancelled_invoice_payment_is_rejected(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $invoice = $this->createIssuedInvoice($organization, $user);
        $invoice->update(['status' => 'cancelled']);

        $response = $this->recordPayment($user, $organization, $invoice, 100);

        $response->assertSessionHasErrors('invoice_id');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_paid_invoice_payment_is_rejected(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $invoice = $this->createIssuedInvoice($organization, $user, 500);
        $invoice->update(['amount_paid' => 500, 'status' => 'paid']);

        $response = $this->recordPayment($user, $organization, $invoice, 100);

        $response->assertSessionHasErrors('invoice_id');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payments_are_immutable(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $invoice = $this->createIssuedInvoice($organization, $user);

        $payment = Payment::factory()->create([
            'organization_id' => $organization->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'amount' => 100,
            'recorded_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete('/payments/'.$payment->id)
            ->assertMethodNotAllowed();

        $this->assertFalse($user->can('update', $payment));
        $this->assertFalse($user->can('delete', $payment));
    }

    public function test_sales_executive_cannot_record_payment(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $invoice = $this->createIssuedInvoice($organization, $user);

        $response = $this->recordPayment($user, $organization, $invoice, 100);

        $response->assertForbidden();
    }

    public function test_payments_are_isolated_by_organization(): void
    {
        [$userA, $orgA] = $this->setupUserWithOrg('manager');
        $orgB = Organization::factory()->create();

        $customerB = Customer::factory()->create(['organization_id' => $orgB->id]);
        $invoiceB = Invoice::factory()->create([
            'organization_id' => $orgB->id,
            'customer_id' => $customerB->id,
            'total' => 1000,
            'amount_paid' => 0,
            'status' => 'issued',
        ]);

        $response = $this->recordPayment($userA, $orgA, $invoiceB, 100);

        $response->assertSessionHasErrors('invoice_id');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_writes_audit_logs(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $invoice = $this->createIssuedInvoice($organization, $user, 1000);

        $this->recordPayment($user, $organization, $invoice, 1000)->assertRedirect();

        $payment = Payment::query()->where('invoice_id', $invoice->id)->first();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Payment::class,
            'auditable_id' => $payment->id,
            'event' => 'recorded',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Invoice::class,
            'auditable_id' => $invoice->id,
            'event' => 'payment_applied',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Invoice::class,
            'auditable_id' => $invoice->id,
            'event' => 'fully_paid',
        ]);
    }

    public function test_payment_notifies_invoice_owner_and_sales_assignee(): void
    {
        Notification::fake();

        [$recorder, $organization] = $this->setupUserWithOrg('manager');
        $invoiceOwner = User::factory()->create();
        $organization->addMember($invoiceOwner, 'manager');

        $salesUser = User::factory()->create();
        $organization->addMember($salesUser, 'manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $recorder->id,
        ]);

        $opportunity = Opportunity::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'assigned_to' => $salesUser->id,
            'created_by' => $recorder->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'opportunity_id' => $opportunity->id,
            'total' => 500,
            'subtotal' => 500,
            'amount_paid' => 0,
            'status' => 'issued',
            'created_by' => $invoiceOwner->id,
        ]);

        $this->recordPayment($recorder, $organization, $invoice, 500)->assertRedirect();

        Notification::assertSentTo($invoiceOwner, CrmNotification::class);
        Notification::assertSentTo($salesUser, CrmNotification::class);
    }

    public function test_transaction_rolls_back_when_audit_fails(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $invoice = $this->createIssuedInvoice($organization, $user, 500);

        $this->mock(AuditLogger::class, function ($mock) {
            $mock->shouldReceive('log')->andThrow(new RuntimeException('Audit failure'));
        });

        $this->expectException(RuntimeException::class);

        try {
            app(\App\Services\PaymentService::class)->record(
                $organization,
                $invoice,
                [
                    'amount' => 100,
                    'payment_date' => now()->toDateString(),
                    'method' => 'bank_transfer',
                ],
                $user,
            );
        } finally {
            $this->assertDatabaseCount('payments', 0);
            $invoice->refresh();
            $this->assertSame(0.0, (float) $invoice->amount_paid);
            $this->assertSame('issued', $invoice->status);
        }
    }
}
