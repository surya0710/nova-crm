<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\CrmActivity;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactActivityTest extends TestCase
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

    public function test_user_can_log_call_meeting_and_follow_up_on_a_contact(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);
        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'name' => 'Jordan Lee',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('contacts.activities.store', $contact), [
                'type' => 'call',
                'subject' => 'Discovery call',
                'body' => 'Discussed timeline.',
                'direction' => 'outbound',
                'outcome' => 'connected',
                'duration_minutes' => 25,
            ])
            ->assertRedirect(route('contacts.show', $contact))
            ->assertSessionHas('status', 'contact-activity-logged');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('contacts.activities.store', $contact), [
                'type' => 'meeting',
                'subject' => 'Kickoff',
                'occurred_at' => now()->toDateTimeString(),
                'duration_minutes' => 45,
            ])
            ->assertRedirect(route('contacts.show', $contact));

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('contacts.activities.store', $contact), [
                'type' => 'follow_up',
                'subject' => 'Send proposal',
                'due_at' => now()->addDay()->toDateTimeString(),
            ])
            ->assertRedirect(route('contacts.show', $contact));

        $this->assertDatabaseHas('crm_activities', [
            'contact_id' => $contact->id,
            'customer_id' => $customer->id,
            'type' => 'call',
            'subject' => 'Discovery call',
        ]);
        $this->assertSame(3, CrmActivity::query()->where('contact_id', $contact->id)->count());
        $this->assertNotNull($customer->fresh()->last_activity_at);
    }

    public function test_contact_timeline_includes_notes_activities_tasks_and_quotation_events(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);
        $contact = Contact::factory()->primary()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'name' => 'Priya Shah',
            'is_decision_maker' => true,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('contacts.notes.store', $contact), [
                'body' => 'Confirmed budget owner.',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('contacts.activities.store', $contact), [
                'type' => 'call',
                'subject' => 'Intro call',
            ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.store'), [
                'title' => 'Send deck',
                'status' => 'pending',
                'priority' => 'medium',
                'taskable_type' => 'contact',
                'taskable_id' => $contact->id,
                'redirect_back' => 1,
            ])
            ->assertSessionHasNoErrors();

        $quotation = Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
            'subtotal' => 100,
            'tax_total' => 0,
            'total' => 100,
            'created_by' => $user->id,
        ]);
        $quotation->items()->create([
            'description' => 'Consulting',
            'quantity' => 1,
            'unit_price' => 100,
            'tax_rate' => 0,
            'discount_percent' => 0,
            'line_total' => 100,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('quotations.status.update', $quotation), ['status' => 'sent'])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('contacts.show', $contact))
            ->assertOk()
            ->assertSee('Confirmed budget owner.')
            ->assertSee('Intro call')
            ->assertSee('Send deck')
            ->assertSee('Contact created')
            ->assertSee(__('Quotation sent'));

        $this->assertTrue(
            Task::query()->where('taskable_id', $contact->id)->where('title', 'Send deck')->exists()
        );
    }

    public function test_hr_cannot_log_contact_activity(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('contacts.activities.store', $contact), [
                'type' => 'call',
                'subject' => 'Should fail',
            ])
            ->assertForbidden();
    }

    public function test_contact_activities_api(): void
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
        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);

        $this->postJson('/api/v1/contacts/'.$contact->id.'/activities', [
            'type' => 'email',
            'subject' => 'Logged email',
            'body' => 'Shared the proposal.',
            'direction' => 'outbound',
        ], $headers)
            ->assertCreated()
            ->assertJsonFragment(['type' => 'email', 'subject' => 'Logged email']);

        $this->getJson('/api/v1/contacts/'.$contact->id.'/timeline', $headers)
            ->assertOk()
            ->assertJsonFragment(['label' => 'Email']);
    }
}
