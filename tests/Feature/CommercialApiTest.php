<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommercialApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Organization}
     */
    protected function setupApiUser(string $role = 'manager'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    /**
     * @return array<string, string>
     */
    protected function apiHeaders(Organization $organization): array
    {
        return [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ];
    }

    public function test_product_catalog_api_crud(): void
    {
        [$user, $organization] = $this->setupApiUser();
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/product-categories', [
            'name' => 'Licences',
            'is_active' => true,
        ], $this->apiHeaders($organization))
            ->assertCreated()
            ->assertJsonFragment(['name' => 'Licences']);

        $category = ProductCategory::query()->first();

        $this->postJson('/api/v1/products', [
            'name' => 'CRM Seat',
            'sku' => 'CRM-SEAT',
            'type' => 'product',
            'unit_price' => 99,
            'currency' => 'USD',
            'status' => 'active',
            'product_category_id' => $category->id,
            'hsn_sac' => '9983',
        ], $this->apiHeaders($organization))
            ->assertCreated()
            ->assertJsonFragment(['sku' => 'CRM-SEAT']);

        $product = Product::query()->first();

        $this->getJson('/api/v1/products/'.$product->id, $this->apiHeaders($organization))
            ->assertOk()
            ->assertJsonFragment(['name' => 'CRM Seat']);
    }

    public function test_customer_tax_profile_is_returned_by_api(): void
    {
        [$user, $organization] = $this->setupApiUser();
        Sanctum::actingAs($user, ['*']);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'gstin' => null,
            'place_of_supply' => '27',
            'shipping_same_as_billing' => true,
        ]);

        $this->getJson('/api/v1/customers/'.$customer->id, $this->apiHeaders($organization))
            ->assertOk()
            ->assertJsonPath('data.place_of_supply', '27')
            ->assertJsonPath('data.shipping_same_as_billing', true);
    }

    public function test_quotation_invoice_and_convert_api(): void
    {
        [$user, $organization] = $this->setupApiUser();
        Sanctum::actingAs($user, ['*']);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $create = $this->postJson('/api/v1/quotations', [
            'customer_id' => $customer->id,
            'title' => 'API Quote',
            'status' => 'draft',
            'issue_date' => now()->toDateString(),
            'currency' => 'USD',
            'items' => [[
                'description' => 'Service hours',
                'quantity' => 2,
                'unit_price' => 100,
                'tax_rate' => 10,
                'discount_percent' => 0,
            ]],
        ], $this->apiHeaders($organization));

        $create->assertCreated();
        $quotationId = $create->json('data.id');
        $this->assertNotNull($quotationId);
        $this->assertSame(220, (int) $create->json('data.total'));
        $this->assertCount(1, $create->json('data.items'));

        $quotation = Quotation::query()->findOrFail($quotationId);
        $quotation->update(['status' => 'accepted']);

        $convert = $this->postJson(
            '/api/v1/quotations/'.$quotation->id.'/convert',
            [],
            $this->apiHeaders($organization),
        );

        $convert->assertCreated();
        $convert->assertJsonPath('data.quotation_id', $quotation->id);
        $this->assertSame(220, (int) $convert->json('data.total'));

        $salesOrderId = $convert->json('data.id');

        $this->getJson('/api/v1/sales-orders/'.$salesOrderId, $this->apiHeaders($organization))
            ->assertOk()
            ->assertJsonPath('data.id', $salesOrderId);

        $invoiceConvert = $this->postJson(
            '/api/v1/sales-orders/'.$salesOrderId.'/convert',
            [],
            $this->apiHeaders($organization),
        );

        $invoiceConvert->assertCreated();
        $invoiceConvert->assertJsonPath('data.sales_order_id', $salesOrderId);
        $invoiceConvert->assertJsonPath('data.quotation_id', $quotation->id);

        $invoiceId = $invoiceConvert->json('data.id');

        $this->getJson('/api/v1/invoices/'.$invoiceId, $this->apiHeaders($organization))
            ->assertOk()
            ->assertJsonPath('data.id', $invoiceId);
    }

    public function test_api_forbidden_without_permission_and_isolates_tenants(): void
    {
        [$hr, $organization] = $this->setupApiUser('hr');
        Sanctum::actingAs($hr, ['*']);

        $this->getJson('/api/v1/products', $this->apiHeaders($organization))->assertForbidden();
        $this->getJson('/api/v1/quotations', $this->apiHeaders($organization))->assertForbidden();
        $this->getJson('/api/v1/sales-orders', $this->apiHeaders($organization))->assertForbidden();
        $this->getJson('/api/v1/invoices', $this->apiHeaders($organization))->assertForbidden();

        [$userA, $orgA] = $this->setupApiUser();
        [$userB, $orgB] = $this->setupApiUser();

        $customer = Customer::factory()->create([
            'organization_id' => $orgA->id,
            'created_by' => $userA->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $orgA->id,
            'customer_id' => $customer->id,
            'created_by' => $userA->id,
        ]);

        Sanctum::actingAs($userB, ['*']);

        $this->getJson('/api/v1/invoices/'.$invoice->id, $this->apiHeaders($orgB))
            ->assertNotFound();
    }

    public function test_adjustment_note_api_create_and_apply(): void
    {
        [$user, $organization] = $this->setupApiUser();
        Sanctum::actingAs($user, ['*']);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);
        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
            'status' => 'issued',
            'total' => 500,
            'amount_paid' => 0,
        ]);

        $create = $this->postJson('/api/v1/adjustment-notes', [
            'type' => 'credit',
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'status' => 'draft',
            'issue_date' => now()->toDateString(),
            'currency' => 'USD',
            'reason' => 'price_adjustment',
            'items' => [[
                'description' => 'API credit',
                'quantity' => 1,
                'unit_price' => 50,
                'tax_rate' => 0,
                'discount_percent' => 0,
            ]],
        ], $this->apiHeaders($organization));

        $create->assertCreated();
        $noteId = $create->json('data.id');
        $this->assertNotNull($noteId);

        $note = \App\Models\AdjustmentNote::query()->findOrFail($noteId);
        app(\App\Services\AdjustmentNoteService::class)->issue($note, $user);

        $this->postJson('/api/v1/adjustment-notes/'.$noteId.'/apply', [], $this->apiHeaders($organization))
            ->assertOk()
            ->assertJsonPath('data.status', 'applied');

        $invoice->refresh();
        $this->assertSame(500.0, (float) $invoice->total);
        $this->assertSame(0.0, (float) $invoice->amount_paid);
        $this->assertSame(450.0, $invoice->effective_balance);
    }

    public function test_payment_allocation_receivables_ledger_and_price_list_apis(): void
    {
        [$user, $organization] = $this->setupApiUser();
        Sanctum::actingAs($user, ['*']);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);
        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
            'status' => 'issued',
            'total' => 300,
            'amount_paid' => 0,
        ]);

        $allocate = $this->postJson('/api/v1/invoices/'.$invoice->id.'/payments', [
            'amount' => 80,
            'payment_date' => now()->toDateString(),
            'method' => 'upi',
            'reference' => 'API-PAY',
        ], $this->apiHeaders($organization));

        $allocate->assertCreated();
        $allocate->assertJsonPath('data.invoice_id', $invoice->id);
        $allocate->assertJsonPath('data.amount', 80);

        $this->getJson('/api/v1/payments', $this->apiHeaders($organization))
            ->assertOk()
            ->assertJsonPath('data.0.amount', 80);

        $this->getJson('/api/v1/receivables', $this->apiHeaders($organization))
            ->assertOk()
            ->assertJsonStructure(['data', 'metrics', 'aging']);

        $this->getJson('/api/v1/customers/'.$customer->id.'/ledger', $this->apiHeaders($organization))
            ->assertOk()
            ->assertJsonPath('data.total_paid', 80);

        $product = Product::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'status' => 'active',
        ]);

        $list = $this->postJson('/api/v1/price-lists', [
            'name' => 'API List',
            'currency' => 'USD',
            'status' => 'active',
            'items' => [[
                'product_id' => $product->id,
                'unit_price' => 42,
                'min_quantity' => 1,
            ]],
        ], $this->apiHeaders($organization));

        $list->assertCreated();
        $list->assertJsonPath('data.name', 'API List');
        $listId = $list->json('data.id');

        $this->getJson('/api/v1/price-lists/'.$listId, $this->apiHeaders($organization))
            ->assertOk()
            ->assertJsonPath('data.items.0.unit_price', 42);

        $credit = $this->postJson('/api/v1/credit-notes', [
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'status' => 'draft',
            'issue_date' => now()->toDateString(),
            'currency' => 'USD',
            'reason' => 'price_adjustment',
            'items' => [[
                'description' => 'API credit alias',
                'quantity' => 1,
                'unit_price' => 10,
                'tax_rate' => 0,
                'discount_percent' => 0,
            ]],
        ], $this->apiHeaders($organization));

        $credit->assertCreated();
        $this->assertSame('credit', $credit->json('data.type'));

        $order = \App\Models\SalesOrder::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);
        $order->items()->create([
            'description' => 'SO item',
            'quantity' => 1,
            'unit_price' => 50,
            'line_total' => 50,
            'sort_order' => 0,
        ]);

        $this->getJson('/api/v1/sales-orders/'.$order->id.'/items', $this->apiHeaders($organization))
            ->assertOk()
            ->assertJsonPath('data.0.description', 'SO item');
    }

    public function test_hr_cannot_access_new_commercial_apis(): void
    {
        [$hr, $organization] = $this->setupApiUser('hr');
        Sanctum::actingAs($hr, ['*']);

        $this->getJson('/api/v1/payments', $this->apiHeaders($organization))->assertForbidden();
        $this->getJson('/api/v1/receivables', $this->apiHeaders($organization))->assertForbidden();
        $this->getJson('/api/v1/price-lists', $this->apiHeaders($organization))->assertForbidden();
        $this->getJson('/api/v1/credit-notes', $this->apiHeaders($organization))->assertForbidden();
    }
}
