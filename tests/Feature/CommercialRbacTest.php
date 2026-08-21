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
use Tests\TestCase;

class CommercialRbacTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Organization}
     */
    protected function setupUserWithOrg(string $role = 'manager'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_hr_cannot_access_commercial_modules(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id]);

        $this->get(route('products.index'))->assertForbidden();
        $this->get(route('product-categories.index'))->assertForbidden();
        $this->get(route('quotations.index'))->assertForbidden();
        $this->get(route('sales-orders.index'))->assertForbidden();
        $this->get(route('invoices.index'))->assertForbidden();
        $this->get(route('credit-notes.index'))->assertForbidden();
        $this->get(route('price-lists.index'))->assertForbidden();
        $this->get(route('receivables.index'))->assertForbidden();
    }

    public function test_sales_executive_can_convert_quotation_to_sales_order(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'accepted',
            'total' => 100,
            'created_by' => $user->id,
        ]);
        $quotation->items()->create([
            'description' => 'Line',
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.convert', $quotation))
            ->assertRedirect();

        $this->assertDatabaseCount('sales_orders', 1);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_sales_executive_cannot_create_product_category(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('product-categories.store'), [
                'name' => 'Blocked',
                'is_active' => true,
            ])
            ->assertForbidden();
    }

    public function test_tenant_isolation_blocks_foreign_invoices_and_quotations(): void
    {
        [$userA, $orgA] = $this->setupUserWithOrg('manager');
        [$userB, $orgB] = $this->setupUserWithOrg('manager');

        $customerA = Customer::factory()->create([
            'organization_id' => $orgA->id,
            'created_by' => $userA->id,
        ]);

        $quotation = Quotation::factory()->create([
            'organization_id' => $orgA->id,
            'customer_id' => $customerA->id,
            'title' => 'Secret Quote',
            'created_by' => $userA->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $orgA->id,
            'customer_id' => $customerA->id,
            'title' => 'Secret Invoice',
            'created_by' => $userA->id,
        ]);

        $product = Product::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Secret Product',
            'created_by' => $userA->id,
        ]);

        $category = ProductCategory::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Secret Category',
            'created_by' => $userA->id,
        ]);

        $this->actingAs($userB)
            ->withSession(['current_organization_id' => $orgB->id]);

        foreach ([
            route('quotations.show', $quotation),
            route('invoices.show', $invoice),
            route('invoices.pdf', $invoice),
            route('products.show', $product),
            route('product-categories.edit', $category),
        ] as $url) {
            $status = $this->get($url)->status();
            $this->assertContains($status, [403, 404], $url.' should be isolated, got '.$status);
        }

        $this->assertContains(
            $this->post(route('invoices.send', $invoice), ['email' => 'x@example.com'])->status(),
            [403, 404],
        );
        $this->assertContains(
            $this->post(route('quotations.convert', $quotation))->status(),
            [403, 404],
        );
    }

    public function test_support_can_view_receivables_and_payments_but_not_create_notes(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('support');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id]);

        $this->get(route('receivables.index'))->assertOk();
        $this->get(route('payments.index'))->assertOk();
        $this->get(route('credit-notes.create'))->assertForbidden();
        $this->get(route('price-lists.index'))->assertForbidden();
    }

    public function test_tenant_isolation_blocks_foreign_sales_orders_payments_and_notes(): void
    {
        [$userA, $orgA] = $this->setupUserWithOrg('manager');
        [$userB, $orgB] = $this->setupUserWithOrg('manager');

        $customerA = Customer::factory()->create([
            'organization_id' => $orgA->id,
            'created_by' => $userA->id,
        ]);

        $order = \App\Models\SalesOrder::factory()->create([
            'organization_id' => $orgA->id,
            'customer_id' => $customerA->id,
            'created_by' => $userA->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $orgA->id,
            'customer_id' => $customerA->id,
            'created_by' => $userA->id,
            'status' => 'issued',
            'total' => 100,
        ]);

        $payment = \App\Models\Payment::factory()->create([
            'organization_id' => $orgA->id,
            'customer_id' => $customerA->id,
            'invoice_id' => $invoice->id,
            'recorded_by' => $userA->id,
        ]);

        $note = \App\Models\AdjustmentNote::factory()->create([
            'organization_id' => $orgA->id,
            'customer_id' => $customerA->id,
            'invoice_id' => $invoice->id,
            'created_by' => $userA->id,
            'type' => 'credit',
        ]);

        $this->actingAs($userB)
            ->withSession(['current_organization_id' => $orgB->id]);

        foreach ([
            route('sales-orders.show', $order),
            route('payments.show', $payment),
            route('credit-notes.show', $note),
        ] as $url) {
            $status = $this->get($url)->status();
            $this->assertContains($status, [403, 404], $url.' should be isolated, got '.$status);
        }
    }
}
