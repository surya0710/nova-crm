<?php

namespace Tests\Feature;

use App\Events\OpportunityWon;
use App\Events\QuotationAccepted;
use App\Events\TicketCreated;
use App\Listeners\AdvanceCustomerLifecycle;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\CustomerTicket;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\SalesTarget;
use App\Models\User;
use App\Services\CustomerLifecycleService;
use App\Services\OpportunityService;
use App\Services\QuotationService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CrmExpansionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Organization}
     */
    protected function setupUserWithOrg(string $role = 'sales-executive'): array
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

    public function test_ticket_workspace_lists_filters_and_shows_sla(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'company' => 'Acme Tickets',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('customers.tickets.store', $customer), [
                'subject' => 'VPN outage',
                'status' => 'open',
                'priority' => 'urgent',
            ])
            ->assertRedirect();

        $ticket = CustomerTicket::query()->where('subject', 'VPN outage')->first();
        $this->assertNotNull($ticket->due_at);
        $this->assertSame(4, $ticket->sla_hours);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('tickets.index', ['priority' => 'urgent', 'status' => 'open']))
            ->assertOk()
            ->assertSee('VPN outage')
            ->assertSee('Acme Tickets');
    }

    public function test_ticket_lifecycle_reopen_and_assignment(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $assignee = User::factory()->create();
        $organization->addMember($assignee, 'sales-executive');
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);
        $ticket = CustomerTicket::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
            'status' => 'open',
            'priority' => 'medium',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('tickets.status', $ticket), ['status' => 'resolved'])
            ->assertRedirect();
        $this->assertSame('resolved', $ticket->fresh()->status);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tickets.reopen', $ticket))
            ->assertRedirect();
        $this->assertSame('open', $ticket->fresh()->status);
        $this->assertNull($ticket->fresh()->resolved_at);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('tickets.assign', $ticket), ['assigned_to' => $assignee->id])
            ->assertRedirect();
        $this->assertSame($assignee->id, $ticket->fresh()->assigned_to);
    }

    public function test_hr_cannot_access_ticket_workspace(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('tickets.index'))
            ->assertForbidden();
    }

    public function test_tickets_are_isolated_across_organizations(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $other = Organization::factory()->create();
        $otherCustomer = Customer::factory()->create([
            'organization_id' => $other->id,
            'created_by' => $user->id,
        ]);
        $foreign = CustomerTicket::factory()->create([
            'organization_id' => $other->id,
            'customer_id' => $otherCustomer->id,
            'subject' => 'Foreign ticket',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('tickets.show', $foreign))
            ->assertForbidden();
    }

    public function test_lifecycle_advances_on_opportunity_won_and_is_idempotent(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'lifecycle_stage' => 'lead',
        ]);
        $opportunity = Opportunity::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'stage' => 'negotiation',
            'created_by' => $user->id,
        ]);

        app(OpportunityService::class)->updateStage($opportunity, [
            'stage' => 'closed_won',
            'won_at' => now()->toDateString(),
        ], $user);

        $event = OpportunityWon::forModel($opportunity->fresh(), ['actor_id' => $user->id]);
        $listener = app(AdvanceCustomerLifecycle::class);
        $listener->handle($event);
        $listener->handle($event);

        $this->assertSame('customer', $customer->fresh()->lifecycle_stage);
        $this->assertDatabaseCount('customer_lifecycle_milestones', 1);
    }

    public function test_quotation_acceptance_advances_lifecycle_to_opportunity(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'lifecycle_stage' => 'lead',
        ]);
        $quotation = Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'sent',
            'total' => 1100,
            'created_by' => $user->id,
        ]);
        $quotation->items()->create([
            'description' => 'Consulting hours',
            'quantity' => 10,
            'unit_price' => 100,
            'tax_rate' => 10,
            'discount_percent' => 0,
            'line_total' => 1100,
            'sort_order' => 0,
        ]);

        Event::fake([QuotationAccepted::class]);
        $transactionManager = app('db.transactions');
        app()->offsetUnset('db.transactions');
        try {
            app(QuotationService::class)->updateStatus($quotation, 'accepted', $user);
        } finally {
            app()->instance('db.transactions', $transactionManager);
        }
        Event::assertDispatched(QuotationAccepted::class);

        app(CustomerLifecycleService::class)->applyMilestone($customer, 'quotation.accepted', $quotation->fresh(), $user);
        $this->assertSame('opportunity', $customer->fresh()->lifecycle_stage);
    }

    public function test_opportunity_pipeline_shows_weighted_metrics_and_source(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('pipeline.store'), [
                'title' => 'Expansion deal',
                'stage' => 'proposal',
                'amount' => 10000,
                'currency' => 'USD',
                'probability' => 50,
                'source' => 'referral',
                'competitor' => 'Acme CRM',
            ])
            ->assertRedirect();

        $opportunity = Opportunity::query()->where('title', 'Expansion deal')->first();
        $this->assertSame('referral', $opportunity->source);
        $this->assertSame(5000.0, $opportunity->weightedAmount());

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('pipeline.index'))
            ->assertOk()
            ->assertSee('Weighted pipeline');
    }

    public function test_unified_sales_activities_page_and_opportunity_logging(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);
        $opportunity = Opportunity::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'stage' => 'qualification',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('pipeline.activities.store', $opportunity), [
                'type' => 'follow_up',
                'subject' => 'Send proposal',
                'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('crm_activities', [
            'opportunity_id' => $opportunity->id,
            'subject' => 'Send proposal',
            'status' => 'open',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('crm.activities', ['scope' => 'upcoming']))
            ->assertOk()
            ->assertSee('Send proposal');
    }

    public function test_crm_apis_and_forecast_are_tenant_scoped(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        Sanctum::actingAs($user, ['*']);
        $headers = $this->apiHeaders($organization);
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $this->postJson('/api/v1/customers/'.$customer->id.'/tickets', [
            'subject' => 'API ticket',
            'status' => 'open',
            'priority' => 'medium',
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('data.subject', 'API ticket')
            ->assertJsonPath('data.is_overdue', false);

        $ticketId = CustomerTicket::query()->where('subject', 'API ticket')->value('id');
        $this->postJson('/api/v1/tickets/'.$ticketId.'/notes', ['body' => 'Working it'], $headers)
            ->assertCreated();

        $this->postJson('/api/v1/opportunities', [
            'title' => 'API deal',
            'customer_id' => $customer->id,
            'stage' => 'qualification',
            'amount' => 2000,
            'currency' => 'USD',
            'probability' => 25,
        ], $headers)
            ->assertCreated();

        Opportunity::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'stage' => 'closed_won',
            'amount' => 8000,
            'won_at' => now(),
            'created_by' => $user->id,
        ]);
        SalesTarget::query()->create([
            'organization_id' => $organization->id,
            'year' => (int) now()->year,
            'month' => (int) now()->month,
            'amount' => 10000,
            'currency' => 'USD',
        ]);

        $this->getJson('/api/v1/sales/forecast', $headers)
            ->assertOk()
            ->assertJsonPath('won_value', 8000)
            ->assertJsonPath('target_amount', 10000);

        $other = Organization::factory()->create();
        $foreignTicket = CustomerTicket::factory()->create([
            'organization_id' => $other->id,
            'customer_id' => Customer::factory()->create(['organization_id' => $other->id])->id,
        ]);
        $this->getJson('/api/v1/tickets/'.$foreignTicket->id, $headers)
            ->assertNotFound();
    }

    public function test_sales_executive_without_api_access_is_forbidden(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/tickets', $this->apiHeaders($organization))
            ->assertForbidden();
    }

    public function test_ticket_created_event_is_emitted(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        Event::fake([TicketCreated::class]);
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $transactionManager = app('db.transactions');
        app()->offsetUnset('db.transactions');
        try {
            $this->actingAs($user)
                ->withSession(['current_organization_id' => $organization->id])
                ->post(route('customers.tickets.store', $customer), [
                    'subject' => 'Event ticket',
                    'status' => 'open',
                    'priority' => 'low',
                ]);
        } finally {
            app()->instance('db.transactions', $transactionManager);
        }

        Event::assertDispatched(TicketCreated::class);
    }
}
