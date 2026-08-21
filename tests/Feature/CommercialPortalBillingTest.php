<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\User;
use App\Services\ClientAccessService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CommercialPortalBillingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Organization, 2: \App\Models\ClientUser, 3: Customer}
     */
    protected function setupPortal(): array
    {
        Notification::fake();

        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');
        app(TenantContext::class)->set($organization);

        $customer = Customer::factory()->create(['organization_id' => $organization->id]);

        $project = app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Shared Build',
            'owner_id' => $user->id,
            'manager_id' => $user->id,
            'priority' => 'medium',
            'client_id' => $customer->id,
        ], $user);

        $client = app(ClientAccessService::class)->invite($organization, $customer, [
            'name' => 'Ada Client',
            'email' => 'ada@client.test',
            'password' => 'password123',
        ], $user);

        app(ClientAccessService::class)->grantProjectAccess($client, $project, config('portal.default_share_scopes'), $user);

        return [$user, $organization, $client, $customer];
    }

    public function test_linked_customer_can_view_own_invoice_and_not_another_customers(): void
    {
        [$user, $organization, $client, $customer] = $this->setupPortal();

        $own = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'issued',
            'total' => 250,
            'created_by' => $user->id,
        ]);

        $otherCustomer = Customer::factory()->create(['organization_id' => $organization->id]);
        $other = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $otherCustomer->id,
            'status' => 'issued',
            'total' => 900,
            'created_by' => $user->id,
        ]);

        $this->actingAs($client, 'client')
            ->get(route('portal.commercial.invoices.show', [$organization, $own]))
            ->assertOk()
            ->assertSee($own->number);

        $this->actingAs($client, 'client')
            ->get(route('portal.commercial.invoices.show', [$organization, $other]))
            ->assertNotFound();
    }

    public function test_customer_can_accept_a_sent_quotation(): void
    {
        [$user, $organization, $client, $customer] = $this->setupPortal();

        $quotation = Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'sent',
            'created_by' => $user->id,
            'total' => 400,
        ]);
        $quotation->items()->create([
            'description' => 'Portal line',
            'quantity' => 1,
            'unit_price' => 400,
            'line_total' => 400,
            'sort_order' => 0,
        ]);

        $this->actingAs($client, 'client')
            ->post(route('portal.commercial.quotations.accept', [$organization, $quotation]))
            ->assertRedirect(route('portal.commercial.quotations.show', [$organization, $quotation]));

        $this->assertSame('accepted', $quotation->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Quotation::class,
            'auditable_id' => $quotation->id,
            'event' => 'portal_accepted',
        ]);
    }

    public function test_portal_billing_api_is_scoped_to_linked_customer(): void
    {
        [$user, $organization, $client, $customer] = $this->setupPortal();

        $own = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'issued',
            'total' => 250,
            'created_by' => $user->id,
        ]);

        $otherCustomer = Customer::factory()->create(['organization_id' => $organization->id]);
        $other = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $otherCustomer->id,
            'status' => 'issued',
            'total' => 900,
            'created_by' => $user->id,
        ]);

        $this->actingAs($client, 'client')
            ->getJson('/api/v1/portal/'.$organization->slug.'/billing')
            ->assertOk()
            ->assertJsonPath('data.linked', true);

        $this->actingAs($client, 'client')
            ->getJson('/api/v1/portal/'.$organization->slug.'/invoices/'.$own->id)
            ->assertOk()
            ->assertJsonPath('data.id', $own->id);

        $this->actingAs($client, 'client')
            ->getJson('/api/v1/portal/'.$organization->slug.'/invoices/'.$other->id)
            ->assertNotFound();

        $this->actingAs($client, 'client')
            ->getJson('/api/v1/portal/'.$organization->slug.'/ledger')
            ->assertOk()
            ->assertJsonStructure(['data' => ['outstanding_balance', 'ledger']]);
    }
}
