<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_user_with_leads_view_can_access_leads_index(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index'));

        $response->assertOk();
        $response->assertSee('Leads');
    }

    public function test_leads_index_renders_bulk_toolbar_and_assign_owner_action(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        Lead::factory()->count(2)->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index'));

        $response->assertOk();
        $response->assertSee('bulkToolbar(', false);
        $response->assertSee('Assign Owner');
        $response->assertSee('Select all filtered records');
    }

    public function test_lookup_users_endpoint_returns_results_for_bulk_assignment_search(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $assignee = User::factory()->create([
            'name' => 'Bulk Assignable User',
            'account_status' => 'active',
        ]);
        $organization->addMember($assignee, 'sales-executive');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->getJson(route('shell.lookups.search', ['entity' => 'users', 'q' => 'Bulk Assignable']));

        $response->assertOk();
        $response->assertJsonPath('data.0.label', 'Bulk Assignable User');
    }

    public function test_user_without_leads_permission_cannot_access_leads(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index'));

        $response->assertForbidden();
    }

    public function test_user_can_create_lead(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.store'), [
                'name' => 'Jane Prospect',
                'company' => 'Acme Ltd',
                'email' => 'jane@acme.test',
                'phone' => '+1 555 0100',
                'source' => 'website',
                'industry' => 'Technology',
                'budget' => 50000,
                'priority' => 'high',
                'status' => 'new',
                'tags' => 'hot, enterprise',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('leads', [
            'organization_id' => $organization->id,
            'name' => 'Jane Prospect',
            'company' => 'Acme Ltd',
            'created_by' => $user->id,
        ]);

        $lead = Lead::query()->where('name', 'Jane Prospect')->first();
        $this->assertEquals(['hot', 'enterprise'], $lead->tags);
    }

    public function test_leads_are_scoped_to_current_organization(): void
    {
        [$userA, $orgA] = $this->setupUserWithOrg('organization-owner');
        $orgB = Organization::factory()->create(['name' => 'Other Org']);

        Lead::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Org A Lead',
            'created_by' => $userA->id,
        ]);

        Lead::factory()->create([
            'organization_id' => $orgB->id,
            'name' => 'Org B Lead',
        ]);

        $response = $this->actingAs($userA)
            ->withSession(['current_organization_id' => $orgA->id])
            ->get(route('leads.index'));

        $response->assertOk();
        $response->assertSee('Org A Lead');
        $response->assertDontSee('Org B Lead');
    }

    public function test_user_can_view_lead_detail(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Detail Lead',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.show', $lead));

        $response->assertOk();
        $response->assertSee('Detail Lead');
        $response->assertSee('Activity');
    }

    public function test_user_can_update_lead(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Old Name',
            'status' => 'new',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('leads.update', $lead), [
                'name' => 'Updated Name',
                'company' => $lead->company,
                'source' => $lead->source,
                'priority' => $lead->priority,
                'status' => 'contacted',
            ]);

        $response->assertRedirect(route('leads.show', $lead));

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'name' => 'Updated Name',
            'status' => 'contacted',
        ]);
    }

    public function test_user_can_delete_lead_with_permission(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('leads.destroy', $lead));

        $response->assertRedirect(route('leads.index'));
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }

    public function test_user_can_add_lead_note(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.notes.store', $lead), [
                'body' => 'Called and left voicemail.',
            ]);

        $response->assertRedirect(route('leads.show', $lead));

        $this->assertDatabaseHas('lead_notes', [
            'lead_id' => $lead->id,
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'body' => 'Called and left voicemail.',
        ]);
    }

    public function test_dashboard_shows_lead_stats(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        Lead::factory()->count(3)->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        Lead::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'won',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Total Leads');
        $response->assertSee('4');
        $response->assertSee('Recent Leads');
    }

    public function test_user_can_update_lead_status_from_show_page(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'new',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('leads.status.update', $lead), [
                'status' => 'won',
            ]);

        $response->assertRedirect(route('leads.show', $lead));
        $response->assertSessionHas('status', 'lead-status-updated');

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => 'won',
        ]);
    }

    public function test_leads_index_filters_by_status(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Open Lead',
            'status' => 'new',
            'created_by' => $user->id,
        ]);

        Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Won Lead',
            'status' => 'won',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', ['status' => 'won']));

        $response->assertOk();
        $response->assertSee('Won Lead');
        $response->assertDontSee('Open Lead');
    }

    public function test_user_can_schedule_next_follow_up_on_lead(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $followUpAt = now()->addDay()->format('Y-m-d\TH:i');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('leads.follow-up.update', $lead), [
                'next_follow_up_at' => $followUpAt,
                'next_follow_up_note' => 'Discuss pricing options',
            ]);

        $response->assertRedirect(route('leads.show', $lead));

        $lead->refresh();
        $this->assertNotNull($lead->next_follow_up_at);
        $this->assertSame('Discuss pricing options', $lead->next_follow_up_note);
    }

    public function test_due_follow_ups_endpoint_returns_leads_needing_alert(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $dueLead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Due Follow Up Lead',
            'status' => 'contacted',
            'next_follow_up_at' => now()->subMinute(),
            'next_follow_up_note' => 'Call back today',
            'created_by' => $user->id,
        ]);

        Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Future Follow Up Lead',
            'status' => 'contacted',
            'next_follow_up_at' => now()->addDay(),
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->getJson(route('leads.follow-ups.due'));

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $dueLead->id);
        $response->assertJsonPath('data.0.name', 'Due Follow Up Lead');
        $response->assertJsonPath('data.0.next_follow_up_note', 'Call back today');
    }

    public function test_acknowledging_follow_up_stops_repeat_alerts(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'contacted',
            'next_follow_up_at' => now()->subMinute(),
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->postJson(route('leads.follow-up.acknowledge', $lead))
            ->assertOk();

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->getJson(route('leads.follow-ups.due'));

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_viewing_due_lead_page_acknowledges_follow_up_alert(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'contacted',
            'next_follow_up_at' => now()->subMinute(),
            'created_by' => $user->id,
        ]);

        $this->assertNull($lead->follow_up_alerted_at);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.show', $lead))
            ->assertOk();

        $lead->refresh();
        $this->assertNotNull($lead->follow_up_alerted_at);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->getJson(route('leads.follow-ups.due'))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_follow_up_uses_organization_timezone(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $organization->update(['timezone' => 'Asia/Kolkata']);

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'contacted',
            'created_by' => $user->id,
        ]);

        $localDueTime = now('Asia/Kolkata')->addHour()->format('Y-m-d\TH:i');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('leads.follow-up.update', $lead), [
                'next_follow_up_at' => $localDueTime,
                'next_follow_up_note' => 'Timezone test',
            ])
            ->assertRedirect();

        $lead->refresh();
        $this->assertTrue($lead->next_follow_up_at->isFuture());
    }

    public function test_past_follow_up_time_is_rejected(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $organization->update(['timezone' => 'Asia/Kolkata']);

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->from(route('leads.show', $lead))
            ->patch(route('leads.follow-up.update', $lead), [
                'next_follow_up_at' => now('Asia/Kolkata')->subHour()->format('Y-m-d\TH:i'),
                'next_follow_up_note' => 'Too early',
            ]);

        $response->assertRedirect(route('leads.show', $lead));
        $response->assertSessionHasErrors('next_follow_up_at');
    }
}
