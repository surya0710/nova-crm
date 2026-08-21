<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'manager'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function createSalesOrder(
        Organization $organization,
        User $user,
        Customer $customer,
        array $overrides = [],
        ?Quotation $quotation = null,
    ): SalesOrder {
        $salesOrder = SalesOrder::factory()->create(array_merge([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'quotation_id' => $quotation?->id,
            'status' => 'confirmed',
            'subtotal' => 1000,
            'discount_amount' => 100,
            'tax_total' => 90,
            'total' => 990,
            'currency' => 'USD',
            'terms' => 'Net 15',
            'created_by' => $user->id,
        ], $overrides));

        $salesOrder->items()->create([
            'description' => 'Consulting package',
            'sku' => 'CONS-1',
            'hsn_sac' => '9983',
            'unit' => 'hour',
            'quantity' => 10,
            'unit_price' => 100,
            'tax_rate' => 10,
            'discount_percent' => 10,
            'line_total' => 990,
            'sort_order' => 0,
        ]);

        return $salesOrder->fresh(['items', 'customer', 'quotation']);
    }

    public function test_sales_order_converts_to_invoice(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);
        $quotation = Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);
        $salesOrder = $this->createSalesOrder($organization, $user, $customer, quotation: $quotation);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('sales-orders.convert', $salesOrder));

        $invoice = Invoice::query()->first();

        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertDatabaseHas('invoices', [
            'sales_order_id' => $salesOrder->id,
            'quotation_id' => $quotation->id,
            'customer_id' => $customer->id,
            'total' => 990,
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('invoice_items', [
            'description' => 'Consulting package',
            'sku' => 'CONS-1',
            'hsn_sac' => '9983',
        ]);
    }

    public function test_duplicate_sales_order_conversion_returns_existing_invoice(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);
        $salesOrder = $this->createSalesOrder($organization, $user, $customer);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('sales-orders.convert', $salesOrder));

        $invoice = Invoice::query()->first();

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('sales-orders.convert', $salesOrder->fresh()));

        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_cancelled_sales_order_cannot_convert(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);
        $salesOrder = $this->createSalesOrder($organization, $user, $customer, ['status' => 'cancelled']);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('sales-orders.convert', $salesOrder));

        $response->assertSessionHasErrors('sales_order');
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_sales_executive_cannot_convert_sales_order_to_invoice(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);
        $salesOrder = $this->createSalesOrder($organization, $user, $customer);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('sales-orders.convert', $salesOrder));

        $response->assertForbidden();
        $this->assertDatabaseCount('invoices', 0);
    }
}
