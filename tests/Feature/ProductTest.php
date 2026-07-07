<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'manager'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_user_with_products_view_can_access_index(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('Products');
    }

    public function test_hr_user_cannot_access_products(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('products.index'));

        $response->assertForbidden();
    }

    public function test_manager_can_create_product(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('products.store'), [
                'name' => 'Premium Support Plan',
                'sku' => 'SUP-001',
                'type' => 'service',
                'unit_price' => 99.00,
                'currency' => 'USD',
                'unit' => 'month',
                'tax_rate' => 10,
                'category' => 'Support',
                'status' => 'active',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('products', [
            'organization_id' => $organization->id,
            'name' => 'Premium Support Plan',
            'sku' => 'SUP-001',
            'created_by' => $user->id,
        ]);
    }

    public function test_sales_executive_cannot_create_product(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('products.store'), [
                'name' => 'Blocked Product',
                'type' => 'product',
                'unit_price' => 50,
                'currency' => 'USD',
                'status' => 'active',
            ]);

        $response->assertForbidden();
    }

    public function test_products_are_scoped_to_organization(): void
    {
        [$user, $orgA] = $this->setupUserWithOrg('manager');
        $orgB = Organization::factory()->create();

        Product::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Org A Product',
            'created_by' => $user->id,
        ]);

        Product::factory()->create([
            'organization_id' => $orgB->id,
            'name' => 'Org B Product',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $orgA->id])
            ->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('Org A Product');
        $response->assertDontSee('Org B Product');
    }

    public function test_manager_can_view_update_and_delete_product(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $product = Product::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'View Test Product',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('View Test Product');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('products.update', $product), [
                'name' => 'Updated Product',
                'sku' => $product->sku,
                'type' => $product->type,
                'unit_price' => 150,
                'currency' => 'USD',
                'status' => 'inactive',
            ])
            ->assertRedirect(route('products.show', $product));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
            'status' => 'inactive',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_sku_must_be_unique_within_organization(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        Product::factory()->create([
            'organization_id' => $organization->id,
            'sku' => 'DUP-001',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('products.store'), [
                'name' => 'Duplicate SKU Product',
                'sku' => 'DUP-001',
                'type' => 'product',
                'unit_price' => 25,
                'currency' => 'USD',
                'status' => 'active',
            ]);

        $response->assertSessionHasErrors('sku');
    }
}
