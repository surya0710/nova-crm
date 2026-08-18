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
        $this->get(route('invoices.index'))->assertForbidden();
    }

    public function test_sales_executive_cannot_convert_quotation(): void
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
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.convert', $quotation))
            ->assertForbidden();
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
}
