<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\InvoiceCalculationService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InvoiceBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'manager'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function sampleItems(array $overrides = []): array
    {
        return [array_merge([
            'description' => 'Monthly service',
            'quantity' => 1,
            'unit_price' => 500,
            'tax_rate' => 10,
            'discount_percent' => 0,
        ], $overrides)];
    }

    protected function createInvoiceWithItems(
        Organization $organization,
        User $user,
        Customer $customer,
        string $status = 'draft',
        array $quotationOverrides = [],
    ): Invoice {
        $invoice = Invoice::factory()->create(array_merge([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => $status,
            'subtotal' => 500,
            'discount_amount' => 0,
            'tax_total' => 50,
            'total' => 550,
            'amount_paid' => 0,
            'created_by' => $user->id,
        ], $quotationOverrides));

        InvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Monthly service',
            'quantity' => 1,
            'unit_price' => 500,
            'tax_rate' => 10,
            'discount_percent' => 0,
            'line_total' => 550,
            'sort_order' => 0,
        ]);

        return $invoice->fresh(['items']);
    }

    public function test_draft_invoice_is_editable(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = $this->createInvoiceWithItems($organization, $user, $customer, 'draft');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('invoices.update', $invoice), [
                'customer_id' => $customer->id,
                'issue_date' => now()->toDateString(),
                'currency' => 'USD',
                'items' => $this->sampleItems(['description' => 'Updated service']),
            ]);

        $response->assertRedirect(route('invoices.show', $invoice));
    }

    public function test_issued_invoice_is_locked_for_financial_edits(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = $this->createInvoiceWithItems($organization, $user, $customer, 'issued');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('invoices.update', $invoice), [
                'customer_id' => $customer->id,
                'issue_date' => $invoice->issue_date->toDateString(),
                'currency' => 'USD',
                'items' => $this->sampleItems(['unit_price' => 999]),
            ]);

        $response->assertSessionHasErrors('items');
    }

    public function test_paid_invoice_is_not_editable(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = $this->createInvoiceWithItems($organization, $user, $customer, 'paid', [
            'amount_paid' => 550,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('invoices.update', $invoice), [
                'customer_id' => $customer->id,
                'issue_date' => now()->toDateString(),
                'currency' => 'USD',
                'items' => $this->sampleItems(),
            ]);

        $response->assertForbidden();
    }

    public function test_cancelled_invoice_is_not_editable(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = $this->createInvoiceWithItems($organization, $user, $customer, 'cancelled');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('invoices.edit', $invoice));

        $response->assertForbidden();
    }

    public function test_draft_can_be_issued(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = $this->createInvoiceWithItems($organization, $user, $customer, 'draft');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('invoices.issue', $invoice));

        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'issued',
        ]);
    }

    public function test_issued_invoice_cannot_be_issued_again(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = $this->createInvoiceWithItems($organization, $user, $customer, 'issued');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('invoices.issue', $invoice));

        $response->assertForbidden();
    }

    public function test_draft_can_be_cancelled(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = $this->createInvoiceWithItems($organization, $user, $customer, 'draft');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('invoices.cancel', $invoice));

        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_paid_invoice_cannot_be_cancelled(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = $this->createInvoiceWithItems($organization, $user, $customer, 'paid');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('invoices.cancel', $invoice));

        $response->assertForbidden();
    }

    public function test_cannot_issue_empty_invoice(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
            'total' => 550,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('invoices.issue', $invoice));

        $response->assertSessionHasErrors('invoice');
    }

    public function test_cannot_issue_zero_value_invoice(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = $this->createInvoiceWithItems($organization, $user, $customer, 'draft', [
            'subtotal' => 0,
            'tax_total' => 0,
            'total' => 0,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('invoices.issue', $invoice));

        $response->assertSessionHasErrors('invoice');
    }

    public function test_balance_due_calculation(): void
    {
        $service = app(InvoiceCalculationService::class);

        $this->assertSame(450.0, $service->balanceDue(550, 100));
        $this->assertSame(0.0, $service->balanceDue(550, 600));
    }

    public function test_invoice_model_balance_due_accessor(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = $this->createInvoiceWithItems($organization, $user, $customer, 'issued', [
            'amount_paid' => 100,
        ]);

        $this->assertSame(450.0, $invoice->balance_due);
    }

    public function test_delete_draft_allowed(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = $this->createInvoiceWithItems($organization, $user, $customer, 'draft');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('invoices.destroy', $invoice));

        $response->assertRedirect(route('invoices.index'));
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    public function test_delete_issued_denied(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = $this->createInvoiceWithItems($organization, $user, $customer, 'issued');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('invoices.destroy', $invoice));

        $response->assertForbidden();
    }

    public function test_delete_paid_denied(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = $this->createInvoiceWithItems($organization, $user, $customer, 'paid');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('invoices.destroy', $invoice));

        $response->assertForbidden();
    }

    public function test_issue_writes_audit_log(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = $this->createInvoiceWithItems($organization, $user, $customer, 'draft');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('invoices.issue', $invoice));

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'auditable_type' => $invoice->getMorphClass(),
            'auditable_id' => $invoice->id,
            'event' => 'issued',
        ]);
    }

    public function test_hr_user_cannot_issue_invoice(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = $this->createInvoiceWithItems($organization, $user, $customer, 'draft');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('invoices.issue', $invoice));

        $response->assertForbidden();
    }

    public function test_organization_isolation_on_create(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $otherOrg = Organization::factory()->create();
        $foreignCustomer = Customer::factory()->create([
            'organization_id' => $otherOrg->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('invoices.store'), [
                'customer_id' => $foreignCustomer->id,
                'status' => 'draft',
                'issue_date' => now()->toDateString(),
                'currency' => 'USD',
                'items' => $this->sampleItems(),
            ]);

        $response->assertSessionHasErrors('customer_id');
    }

    public function test_transaction_rolls_back_on_issue_failure(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = $this->createInvoiceWithItems($organization, $user, $customer, 'draft');

        $this->mock(AuditLogger::class, function ($mock) {
            $mock->shouldReceive('log')
                ->andReturnUsing(function ($model, $event) {
                    if ($event === 'issued') {
                        throw new \RuntimeException('Simulated issue failure');
                    }

                    return new AuditLog([
                        'organization_id' => 1,
                        'event' => $event,
                        'subject' => 'test',
                    ]);
                });
        });

        try {
            app(InvoiceService::class)->issue($invoice, $user);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'draft',
        ]);
    }

    public function test_cancel_writes_audit_log_and_notification(): void
    {
        Notification::fake();

        [$actor, $organization] = $this->setupUserWithOrg('manager');
        $creator = User::factory()->create();
        $organization->addMember($creator, 'manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $creator->id,
        ]);

        $invoice = $this->createInvoiceWithItems($organization, $creator, $customer, 'issued');

        $this->actingAs($actor)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('invoices.cancel', $invoice));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'cancelled',
            'auditable_id' => $invoice->id,
        ]);

        Notification::assertSentTo($creator, \App\Notifications\CrmNotification::class);
    }
}
