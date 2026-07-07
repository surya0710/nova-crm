<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_user_with_customers_view_can_access_index(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee('Customers');
    }

    public function test_user_without_customers_permission_cannot_access(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index'));

        $response->assertForbidden();
    }

    public function test_user_can_create_customer(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('customers.store'), [
                'name' => 'John Smith',
                'company' => 'Smith Industries',
                'email' => 'john@smith.test',
                'phone' => '+1 555 0200',
                'status' => 'active',
                'city' => 'Mumbai',
                'country' => 'India',
                'tags' => 'vip, enterprise',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'organization_id' => $organization->id,
            'name' => 'John Smith',
            'company' => 'Smith Industries',
            'created_by' => $user->id,
        ]);

        $customer = Customer::query()->where('company', 'Smith Industries')->first();
        $this->assertEquals(['vip', 'enterprise'], $customer->tags);
    }

    public function test_customers_are_scoped_to_organization(): void
    {
        [$user, $orgA] = $this->setupUserWithOrg('manager');
        $orgB = Organization::factory()->create();

        Customer::factory()->create([
            'organization_id' => $orgA->id,
            'company' => 'Org A Customer',
            'created_by' => $user->id,
        ]);

        Customer::factory()->create([
            'organization_id' => $orgB->id,
            'company' => 'Org B Customer',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $orgA->id])
            ->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee('Org A Customer');
        $response->assertDontSee('Org B Customer');
    }

    public function test_user_can_view_update_and_delete_customer(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'View Test',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('View Test');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('customers.update', $customer), [
                'name' => 'Updated Name',
                'company' => $customer->company,
                'status' => 'inactive',
            ])
            ->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Name',
            'status' => 'inactive',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_user_can_add_customer_note(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('customers.notes.store', $customer), [
                'body' => 'Annual contract renewed.',
            ]);

        $response->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseHas('customer_notes', [
            'customer_id' => $customer->id,
            'body' => 'Annual contract renewed.',
        ]);
    }

    public function test_dashboard_shows_customer_count(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        Customer::factory()->count(2)->create([
            'organization_id' => $organization->id,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        Customer::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'prospect',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('3');
    }
}
