<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\User;
use App\Services\PriceResolutionService;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceListTest extends TestCase
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

    public function test_manager_can_view_price_lists(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('price-lists.index'))
            ->assertOk();
    }

    public function test_hr_cannot_access_price_lists(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('price-lists.index'))
            ->assertForbidden();
    }

    public function test_customer_quantity_break_beats_catalog_price(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);
        $product = Product::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'unit_price' => 100,
            'status' => 'active',
            'default_discount_percent' => 0,
        ]);

        $list = PriceList::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'is_default' => false,
            'status' => 'active',
        ]);
        $list->items()->create([
            'product_id' => $product->id,
            'unit_price' => 80,
            'min_quantity' => 5,
        ]);
        $list->customers()->attach($customer->id, ['priority' => 10]);

        $resolved = app(PriceResolutionService::class)->resolve($product, $customer, 5);

        $this->assertSame(80.0, $resolved['unit_price']);
        $this->assertSame('price_list', $resolved['source']);
        $this->assertSame($list->id, $resolved['price_list_id']);

        $catalog = app(PriceResolutionService::class)->resolve($product, $customer, 1);
        $this->assertSame(100.0, $catalog['unit_price']);
        $this->assertSame('catalog', $catalog['source']);
    }

    public function test_updating_catalog_price_writes_history(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $product = Product::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'unit_price' => 40,
            'status' => 'active',
        ]);

        app(ProductService::class)->update($product, [
            'name' => $product->name,
            'sku' => $product->sku,
            'type' => $product->type,
            'unit_price' => 55,
            'currency' => $product->currency,
            'unit' => $product->unit,
            'tax_rate' => $product->tax_rate,
            'status' => 'active',
        ], [], $user);

        $this->assertDatabaseHas('product_price_histories', [
            'product_id' => $product->id,
            'old_unit_price' => 40,
            'new_unit_price' => 55,
            'changed_by' => $user->id,
        ]);
        $this->assertSame(1, ProductPriceHistory::query()->count());
    }

    public function test_quotation_create_prefills_from_opportunity(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'company' => 'Northwind',
        ]);
        $opportunity = Opportunity::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
            'title' => 'Warehouse rollout',
            'currency' => 'USD',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('quotations.create', ['opportunity' => $opportunity->id]))
            ->assertOk()
            ->assertSee('Warehouse rollout')
            ->assertSee('Northwind');
    }
}
