<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\QuotationConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class QuotationConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'manager'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function createAcceptedQuotation(
        Organization $organization,
        User $user,
        Customer $customer,
        array $itemOverrides = [],
        array $quotationOverrides = [],
    ): Quotation {
        $quotation = Quotation::factory()->create(array_merge([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'accepted',
            'subtotal' => 1000,
            'discount_amount' => 100,
            'tax_total' => 90,
            'total' => 990,
            'currency' => 'USD',
            'notes' => 'Net 30 terms apply.',
            'created_by' => $user->id,
        ], $quotationOverrides));

        $quotation->items()->create([
            'description' => $itemOverrides['description'] ?? 'Consulting package',
            'product_id' => $itemOverrides['product_id'] ?? null,
            'quantity' => $itemOverrides['quantity'] ?? 10,
            'unit_price' => $itemOverrides['unit_price'] ?? 100,
            'tax_rate' => $itemOverrides['tax_rate'] ?? 10,
            'discount_percent' => $itemOverrides['discount_percent'] ?? 10,
            'line_total' => $itemOverrides['line_total'] ?? 990,
            'sort_order' => 0,
        ]);

        return $quotation->fresh(['items', 'customer']);
    }

    public function test_accepted_quotation_converts_successfully(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createAcceptedQuotation($organization, $user, $customer);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.convert', $quotation));

        $invoice = Invoice::query()->first();

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('status', 'invoice-created-from-quotation');

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => 'converted',
        ]);

        $this->assertDatabaseHas('invoices', [
            'quotation_id' => $quotation->id,
            'customer_id' => $customer->id,
            'subtotal' => 1000,
            'discount_amount' => 100,
            'tax_total' => 90,
            'total' => 990,
            'currency' => 'USD',
            'status' => 'draft',
        ]);
    }

    public function test_draft_quotation_cannot_convert(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createAcceptedQuotation($organization, $user, $customer, quotationOverrides: [
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.convert', $quotation));

        $response->assertRedirect(route('quotations.show', $quotation));
        $response->assertSessionHasErrors('quotation');
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_sent_quotation_cannot_convert(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createAcceptedQuotation($organization, $user, $customer, quotationOverrides: [
            'status' => 'sent',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.convert', $quotation));

        $response->assertSessionHasErrors('quotation');
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_rejected_quotation_cannot_convert(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createAcceptedQuotation($organization, $user, $customer, quotationOverrides: [
            'status' => 'rejected',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.convert', $quotation));

        $response->assertSessionHasErrors('quotation');
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_expired_quotation_cannot_convert(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createAcceptedQuotation($organization, $user, $customer, quotationOverrides: [
            'status' => 'expired',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.convert', $quotation));

        $response->assertSessionHasErrors('quotation');
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_converted_quotation_returns_existing_invoice(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createAcceptedQuotation($organization, $user, $customer);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.convert', $quotation));

        $invoice = Invoice::query()->first();

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.convert', $quotation->fresh()));

        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_invoice_totals_equal_quotation_totals(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createAcceptedQuotation($organization, $user, $customer);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.convert', $quotation));

        $invoice = Invoice::query()->with('items')->first();

        $this->assertSame((float) $quotation->subtotal, (float) $invoice->subtotal);
        $this->assertSame((float) $quotation->discount_amount, (float) $invoice->discount_amount);
        $this->assertSame((float) $quotation->tax_total, (float) $invoice->tax_total);
        $this->assertSame((float) $quotation->total, (float) $invoice->total);
    }

    public function test_line_items_copied_correctly(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createAcceptedQuotation($organization, $user, $customer, [
            'description' => 'Premium support',
            'quantity' => 5,
            'unit_price' => 200,
            'tax_rate' => 8,
            'discount_percent' => 5,
            'line_total' => 990,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.convert', $quotation));

        $this->assertDatabaseHas('invoice_items', [
            'description' => 'Premium support',
            'quantity' => 5,
            'unit_price' => 200,
            'tax_rate' => 8,
            'discount_percent' => 5,
            'line_total' => 990,
            'sort_order' => 0,
        ]);
    }

    public function test_transaction_rolls_back_on_failure(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createAcceptedQuotation($organization, $user, $customer);

        $this->mock(AuditLogger::class, function ($mock) {
            $mock->shouldReceive('log')
                ->andReturnUsing(function ($model, $event) {
                    if ($event === 'converted') {
                        throw new \RuntimeException('Simulated conversion failure');
                    }

                    return new AuditLog([
                        'organization_id' => 1,
                        'event' => $event,
                        'subject' => 'test',
                    ]);
                });
        });

        try {
            app(QuotationConversionService::class)->convert($quotation, $user);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_items', 0);
        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => 'accepted',
        ]);
    }

    public function test_audit_logs_written_on_conversion(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createAcceptedQuotation($organization, $user, $customer);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.convert', $quotation));

        $invoice = Invoice::query()->first();

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'auditable_type' => $quotation->getMorphClass(),
            'auditable_id' => $quotation->id,
            'event' => 'converted',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'auditable_type' => $invoice->getMorphClass(),
            'auditable_id' => $invoice->id,
            'event' => 'created_from_quotation',
        ]);
    }

    public function test_authorization_enforced_for_conversion(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createAcceptedQuotation($organization, $user, $customer);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.convert', $quotation));

        $response->assertForbidden();
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_duplicate_conversion_blocked_when_active_invoice_exists(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createAcceptedQuotation($organization, $user, $customer);

        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'quotation_id' => $quotation->id,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.convert', $quotation));

        $existingInvoice = Invoice::query()->where('quotation_id', $quotation->id)->first();

        $response->assertRedirect(route('invoices.show', $existingInvoice));
        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_relationship_established_between_quotation_and_invoice(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = $this->createAcceptedQuotation($organization, $user, $customer);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.convert', $quotation));

        $quotation->refresh();

        $this->assertNotNull($quotation->invoice);
        $this->assertSame($quotation->id, $quotation->invoice->quotation_id);
    }

    public function test_notification_sent_to_quotation_creator(): void
    {
        Notification::fake();

        [$converter, $organization] = $this->setupUserWithOrg('manager');

        $creator = User::factory()->create();
        $organization->addMember($creator, 'manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $creator->id,
        ]);

        $quotation = $this->createAcceptedQuotation($organization, $creator, $customer);

        $this->actingAs($converter)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.convert', $quotation));

        Notification::assertSentTo($creator, \App\Notifications\CrmNotification::class);
    }
}
