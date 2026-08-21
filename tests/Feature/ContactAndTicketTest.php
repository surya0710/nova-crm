<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\CustomerTicket;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactAndTicketTest extends TestCase
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

    public function test_user_can_manage_contacts_on_a_customer(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'company' => 'Acme Corp',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('customers.contacts.store', $customer), [
                'name' => 'Alex Rivera',
                'title' => 'CFO',
                'department' => 'Finance',
                'email' => 'alex@acme.test',
                'phone' => '+1 555 0100',
                'whatsapp' => '+1 555 0100',
                'is_primary' => '1',
                'is_decision_maker' => '1',
                'status' => 'active',
            ])
            ->assertRedirect();

        $contact = Contact::query()->where('email', 'alex@acme.test')->first();
        $this->assertNotNull($contact);
        $this->assertTrue($contact->is_primary);
        $this->assertTrue($contact->is_decision_maker);
        $this->assertSame('Alex Rivera', $customer->fresh()->name);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('contacts.notes.store', $contact), [
                'body' => 'Confirmed budget owner.',
            ])
            ->assertRedirect(route('contacts.show', $contact));

        $this->assertDatabaseHas('contact_notes', [
            'contact_id' => $contact->id,
            'body' => 'Confirmed budget owner.',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('contacts.index'))
            ->assertOk()
            ->assertSee('Alex Rivera')
            ->assertSee('Acme Corp');
    }

    public function test_only_one_primary_contact_is_kept(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $first = Contact::factory()->primary()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('customers.contacts.store', $customer), [
                'name' => 'Second Primary',
                'status' => 'active',
                'is_primary' => '1',
            ])
            ->assertRedirect();

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertSame(1, $customer->contacts()->where('is_primary', true)->count());
    }

    public function test_hr_cannot_access_contacts(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('contacts.index'))
            ->assertForbidden();
    }

    public function test_user_can_create_company_ticket(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);
        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('customers.tickets.store', $customer), [
                'subject' => 'Billing question',
                'body' => 'Need a copy of the last invoice.',
                'status' => 'open',
                'priority' => 'high',
                'contact_id' => $contact->id,
            ])
            ->assertRedirect();

        $ticket = CustomerTicket::query()->where('subject', 'Billing question')->first();
        $this->assertNotNull($ticket);
        $this->assertSame($customer->id, $ticket->customer_id);
        $this->assertNotEmpty($ticket->number);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Billing question')
            ->assertSee($contact->name);
    }

    public function test_contacts_api_crud(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        Sanctum::actingAs($user, ['*']);
        $headers = [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ];

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $this->postJson('/api/v1/customers/'.$customer->id.'/contacts', [
            'name' => 'API Contact',
            'email' => 'api@acme.test',
            'status' => 'active',
            'is_primary' => true,
        ], $headers)
            ->assertCreated()
            ->assertJsonFragment(['name' => 'API Contact']);

        $contact = Contact::query()->where('email', 'api@acme.test')->first();

        $this->getJson('/api/v1/contacts/'.$contact->id, $headers)
            ->assertOk()
            ->assertJsonFragment(['email' => 'api@acme.test']);
    }
}
