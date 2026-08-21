<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Support\Gstin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialFoundationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Organization}
     */
    protected function setupUserWithOrg(string $role = 'manager'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create([
            'gst_state_code' => '27',
            'state' => 'Maharashtra',
        ]);
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function validGstin(string $state = '27'): string
    {
        $base = $state.'ABCDE1234F1Z';

        return $base.Gstin::checksumCharacter($base);
    }

    public function test_manager_can_create_product_category_and_catalog_item(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('product-categories.store'), [
                'name' => 'Consulting',
                'is_active' => '1',
            ])
            ->assertRedirect(route('product-categories.index'));

        $category = ProductCategory::query()->first();
        $this->assertNotNull($category);
        $this->assertSame($organization->id, $category->organization_id);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('products.store'), [
                'name' => 'Retainer',
                'sku' => 'RET-100',
                'type' => 'service',
                'unit_price' => 25000,
                'cost_price' => 12000,
                'currency' => 'INR',
                'unit' => 'month',
                'tax_rate' => 18,
                'default_discount_percent' => 5,
                'hsn_sac' => '9983',
                'product_category_id' => $category->id,
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'organization_id' => $organization->id,
            'sku' => 'RET-100',
            'hsn_sac' => '9983',
            'category' => 'Consulting',
            'cost_price' => 12000,
        ]);
    }

    public function test_invalid_gstin_is_rejected_and_valid_profile_is_saved(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('customers.store'), [
                'name' => 'Bad GST',
                'status' => 'active',
                'gstin' => '27INVALIDGSTIN1',
            ])
            ->assertSessionHasErrors('gstin');

        $gstin = $this->validGstin();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('customers.store'), [
                'name' => 'Acme India',
                'company' => 'Acme Pvt Ltd',
                'status' => 'active',
                'gstin' => $gstin,
                'gst_registration_type' => 'regular',
                'tax_registration_status' => 'registered',
                'billing_state_code' => '27',
                'place_of_supply' => '29',
                'default_tax_preference' => 'exclusive',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'organization_id' => $organization->id,
            'gstin' => $gstin,
            'pan' => 'ABCDE1234F',
            'place_of_supply' => '29',
            'billing_state_code' => '27',
        ]);
    }

    public function test_quotation_stores_gst_split_and_converts_snapshot_to_invoice(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'gstin' => $this->validGstin('29'),
            'billing_state_code' => '29',
            'place_of_supply' => '29',
            'default_tax_preference' => 'exclusive',
        ]);

        $product = Product::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => 'CRM Licence',
            'sku' => 'CRM-1',
            'hsn_sac' => '9983',
            'unit_price' => 1000,
            'tax_rate' => 18,
            'status' => 'active',
            'currency' => 'INR',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.store'), [
                'customer_id' => $customer->id,
                'status' => 'draft',
                'issue_date' => now()->toDateString(),
                'valid_until' => now()->addDays(15)->toDateString(),
                'currency' => 'INR',
                'pricing_mode' => 'exclusive',
                'tax_treatment' => 'standard',
                'place_of_supply' => '29',
                'terms' => 'Net 15. GST extra as applicable.',
                'items' => [[
                    'product_id' => $product->id,
                    'sku' => 'CRM-1',
                    'hsn_sac' => '9983',
                    'unit' => 'each',
                    'description' => 'CRM Licence',
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'tax_rate' => 18,
                    'discount_percent' => 0,
                ]],
            ])
            ->assertRedirect();

        $quotation = \App\Models\Quotation::query()->first();
        $this->assertSame(180.0, (float) $quotation->igst_amount);
        $this->assertSame(0.0, (float) $quotation->cgst_amount);
        $this->assertSame(1180.0, (float) $quotation->total);
        $this->assertSame('Net 15. GST extra as applicable.', $quotation->terms);
        $this->assertSame('CRM-1', $quotation->items()->first()->sku);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('quotations.pdf', $quotation));

        $response->assertOk();
        $content = $response->getContent();
        if ($content === '' || $content === false) {
            $content = $response->streamedContent();
        }
        $this->assertStringStartsWith('%PDF', (string) $content);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('quotations.status.update', $quotation), ['status' => 'sent'])
            ->assertRedirect();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('quotations.status.update', $quotation->fresh()), ['status' => 'accepted'])
            ->assertRedirect();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.convert', $quotation->fresh()))
            ->assertRedirect();

        $salesOrder = \App\Models\SalesOrder::query()->first();
        $this->assertSame((float) $quotation->total, (float) $salesOrder->total);
        $this->assertSame((float) $quotation->igst_amount, (float) $salesOrder->igst_amount);
        $this->assertSame($quotation->terms, $salesOrder->terms);
        $this->assertSame('CRM-1', $salesOrder->items()->first()->sku);
        $this->assertSame('converted', $quotation->fresh()->status);
        $this->assertNotEmpty($salesOrder->billing_snapshot);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('sales-orders.convert', $salesOrder))
            ->assertRedirect();

        $invoice = \App\Models\Invoice::query()->first();
        $this->assertSame((float) $quotation->total, (float) $invoice->total);
        $this->assertSame((float) $quotation->igst_amount, (float) $invoice->igst_amount);
        $this->assertSame($quotation->terms, $invoice->terms);
        $this->assertSame('CRM-1', $invoice->items()->first()->sku);
        $this->assertSame($salesOrder->id, $invoice->sales_order_id);
        $this->assertSame($quotation->id, $invoice->quotation_id);
        $this->assertNotEmpty($invoice->billing_snapshot);
        $this->assertSame($quotation->billing_snapshot['gstin'] ?? $customer->gstin, $invoice->billing_snapshot['gstin'] ?? null);

        $pdf = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('invoices.pdf', $invoice));

        $pdf->assertOk();
        $pdfContent = $pdf->getContent();
        if ($pdfContent === '' || $pdfContent === false) {
            $pdfContent = $pdf->streamedContent();
        }
        $this->assertStringStartsWith('%PDF', (string) $pdfContent);
    }

    public function test_existing_simple_tax_quotation_still_creates(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.store'), [
                'customer_id' => $customer->id,
                'title' => 'Website Project',
                'status' => 'draft',
                'issue_date' => now()->toDateString(),
                'currency' => 'USD',
                'items' => [[
                    'description' => 'Consulting hours',
                    'quantity' => 10,
                    'unit_price' => 100,
                    'tax_rate' => 10,
                    'discount_percent' => 0,
                ]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('quotations', [
            'title' => 'Website Project',
            'subtotal' => 1000,
            'tax_total' => 100,
            'total' => 1100,
            'other_tax_amount' => 100,
        ]);
    }
}
