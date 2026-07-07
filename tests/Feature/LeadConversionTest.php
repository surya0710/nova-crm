<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_convertible_lead_shows_convert_button(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'qualified',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.show', $lead));

        $response->assertOk();
        $response->assertSee('Convert Lead');
    }

    public function test_converted_lead_does_not_show_convert_button(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'converted',
            'converted_at' => now(),
            'converted_by' => $user->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.show', $lead));

        $response->assertOk();
        $response->assertDontSee('Convert Lead');
    }

    public function test_user_can_convert_qualified_lead(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'qualified',
            'name' => 'Jane Prospect',
            'email' => 'jane@example.test',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.convert', $lead), [
                'name' => 'Jane Prospect',
                'email' => 'jane@example.test',
                'create_opportunity' => '1',
            ]);

        $customer = Customer::query()->where('email', 'jane@example.test')->first();

        $response->assertRedirect(route('customers.show', $customer));
        $response->assertSessionHas('status', 'lead-converted-with-opportunity');

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => 'converted',
        ]);

        $this->assertDatabaseHas('customers', [
            'lead_id' => $lead->id,
            'status' => 'prospect',
        ]);

        $this->assertDatabaseHas('opportunities', [
            'lead_id' => $lead->id,
            'customer_id' => $customer->id,
        ]);
    }

    public function test_duplicate_customer_shows_resolution_flow(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $existing = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'shared@example.test',
            'created_by' => $user->id,
        ]);

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'qualified',
            'email' => 'shared@example.test',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.convert', $lead), [
                'name' => 'Jane Prospect',
                'email' => 'shared@example.test',
                'create_opportunity' => '0',
            ]);

        $response->assertRedirect(route('leads.show', $lead));
        $response->assertSessionHas('duplicate_customers');
        $response->assertSessionHasErrors('duplicate_customer');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.convert', $lead), [
                'name' => 'Jane Prospect',
                'email' => 'shared@example.test',
                'create_opportunity' => '0',
                'existing_customer_id' => $existing->id,
            ]);

        $response->assertRedirect(route('customers.show', $existing));
        $response->assertSessionHas('status', 'lead-converted');
    }

    public function test_user_without_permission_cannot_convert(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('employee');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'qualified',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.convert', $lead), [
                'name' => 'Jane Prospect',
            ]);

        $response->assertForbidden();
    }
}
