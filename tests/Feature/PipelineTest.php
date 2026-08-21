<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'sales-executive'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_user_can_access_pipeline_index(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('pipeline.index'));

        $response->assertOk();
        $response->assertSee('Pipeline');
        $response->assertSee('Open Deals');
    }

    public function test_hr_user_cannot_access_pipeline(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('pipeline.index'));

        $response->assertForbidden();
    }

    public function test_user_can_create_opportunity(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $customer = Customer::factory()->create(['organization_id' => $organization->id, 'created_by' => $user->id]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('pipeline.store'), [
                'title' => 'Annual Support Contract',
                'customer_id' => $customer->id,
                'stage' => 'qualification',
                'amount' => 50000,
                'currency' => 'USD',
                'probability' => 40,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('opportunities', [
            'organization_id' => $organization->id,
            'title' => 'Annual Support Contract',
            'customer_id' => $customer->id,
        ]);
    }

    public function test_cannot_create_opportunity_in_closed_stage(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('pipeline.store'), [
                'title' => 'Invalid Deal',
                'stage' => 'closed_won',
                'currency' => 'USD',
            ]);

        $response->assertSessionHasErrors('stage');
    }

    public function test_opportunities_are_scoped_to_organization(): void
    {
        [$user, $orgA] = $this->setupUserWithOrg('manager');
        $orgB = Organization::factory()->create();

        Opportunity::factory()->create([
            'organization_id' => $orgA->id,
            'title' => 'Org A Deal',
            'created_by' => $user->id,
        ]);

        Opportunity::factory()->create([
            'organization_id' => $orgB->id,
            'title' => 'Org B Deal',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $orgA->id])
            ->get(route('pipeline.index'));

        $response->assertOk();
        $response->assertSee('Org A Deal');
        $response->assertDontSee('Org B Deal');
    }

    public function test_user_can_move_opportunity_between_open_stages(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $opportunity = Opportunity::factory()->create([
            'organization_id' => $organization->id,
            'stage' => 'qualification',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->from(route('pipeline.show', $opportunity))
            ->patch(route('pipeline.stage.update', $opportunity), [
                'stage' => 'proposal',
            ]);

        $response->assertRedirect(route('pipeline.show', $opportunity));
        $response->assertSessionHas('status', 'opportunity-stage-updated');

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'stage' => 'proposal',
        ]);
    }

    public function test_user_can_mark_opportunity_as_won_with_date(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $opportunity = Opportunity::factory()->create([
            'organization_id' => $organization->id,
            'stage' => 'negotiation',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->from(route('pipeline.show', $opportunity))
            ->patch(route('pipeline.stage.update', $opportunity), [
                'stage' => 'closed_won',
                'won_at' => '2026-07-07',
            ]);

        $response->assertRedirect(route('pipeline.show', $opportunity));
        $response->assertSessionHas('status', 'opportunity-won');

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'stage' => 'closed_won',
            'won_at' => '2026-07-07',
            'lost_reason' => null,
        ]);
    }

    public function test_marking_won_requires_won_date(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $opportunity = Opportunity::factory()->create([
            'organization_id' => $organization->id,
            'stage' => 'proposal',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('pipeline.stage.update', $opportunity), [
                'stage' => 'closed_won',
            ]);

        $response->assertSessionHasErrors('won_at');
    }

    public function test_user_can_mark_opportunity_as_lost_with_reason(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $opportunity = Opportunity::factory()->create([
            'organization_id' => $organization->id,
            'stage' => 'proposal',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->from(route('pipeline.show', $opportunity))
            ->patch(route('pipeline.stage.update', $opportunity), [
                'stage' => 'closed_lost',
                'lost_reason' => 'Chose a competitor',
            ]);

        $response->assertRedirect(route('pipeline.show', $opportunity));
        $response->assertSessionHas('status', 'opportunity-lost');

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'stage' => 'closed_lost',
            'lost_reason' => 'Chose a competitor',
            'won_at' => null,
        ]);
    }

    public function test_marking_lost_requires_reason(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $opportunity = Opportunity::factory()->create([
            'organization_id' => $organization->id,
            'stage' => 'qualification',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('pipeline.stage.update', $opportunity), [
                'stage' => 'closed_lost',
            ]);

        $response->assertSessionHasErrors('lost_reason');
    }

    public function test_closed_opportunity_cannot_change_stage(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $opportunity = Opportunity::factory()->create([
            'organization_id' => $organization->id,
            'stage' => 'closed_won',
            'won_at' => now(),
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('pipeline.stage.update', $opportunity), [
                'stage' => 'qualification',
            ]);

        $response->assertSessionHasErrors('stage');

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'stage' => 'closed_won',
        ]);
    }

    public function test_pipeline_index_shows_summary_counts(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        Opportunity::factory()->create([
            'organization_id' => $organization->id,
            'stage' => 'qualification',
            'amount' => 10000,
            'created_by' => $user->id,
        ]);

        Opportunity::factory()->create([
            'organization_id' => $organization->id,
            'stage' => 'closed_won',
            'won_at' => now(),
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('pipeline.index'));

        $response->assertOk();
        $response->assertSee('Open Pipeline Value');
        $response->assertSee('10,000');
    }
}
