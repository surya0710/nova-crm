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

    public function test_user_can_update_stage_from_show_page(): void
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
                'stage' => 'closed_won',
            ]);

        $response->assertRedirect(route('pipeline.show', $opportunity));

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'stage' => 'closed_won',
        ]);
    }
}
