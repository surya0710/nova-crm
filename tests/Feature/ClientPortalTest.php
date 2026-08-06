<?php

namespace Tests\Feature;

use App\Models\ClientApproval;
use App\Models\Customer;
use App\Models\Deliverable;
use App\Models\Organization;
use App\Models\User;
use App\Services\ClientAccessService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use RefreshDatabase;

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

        return [$user, $organization, $project, $client, $customer];
    }

    public function test_client_can_login_and_view_dashboard(): void
    {
        [, $organization, , $client] = $this->setupPortal();

        $this->post(route('portal.login', $organization), [
            'email' => 'ada@client.test',
            'password' => 'password123',
        ])->assertRedirect(route('portal.dashboard', $organization));

        $this->actingAs($client, 'client')
            ->get(route('portal.dashboard', $organization))
            ->assertOk()
            ->assertSee('Shared Build');
    }

    public function test_ungranted_project_returns_404(): void
    {
        [$user, $organization, , $client] = $this->setupPortal();

        $other = app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Secret',
            'owner_id' => $user->id,
            'manager_id' => $user->id,
            'priority' => 'medium',
        ], $user);

        $this->actingAs($client, 'client')
            ->get(route('portal.projects.show', [$organization, $other]))
            ->assertNotFound();
    }

    public function test_staff_can_create_deliverable_and_client_can_approve(): void
    {
        [$user, $organization, $project, $client] = $this->setupPortal();

        $deliverable = app(\App\Services\DeliverableService::class)->create($project, [
            'title' => 'Wireframes',
        ], $user);
        app(\App\Services\DeliverableService::class)->submit($deliverable, $user);

        $approval = app(\App\Services\ApprovalService::class)->createForDeliverable($deliverable->fresh(), $user);
        $this->assertSame('client_review', $approval->status);

        $this->actingAs($client, 'client')
            ->post(route('portal.approvals.approve', [$organization, $approval]), [
                'decision_notes' => 'Approved',
            ])
            ->assertRedirect();

        $this->assertSame('approved', $approval->fresh()->status);
        $this->assertSame('approved', $deliverable->fresh()->status);
    }

    public function test_portal_api_dashboard(): void
    {
        [, $organization, , $client] = $this->setupPortal();

        $this->actingAs($client, 'client')
            ->getJson('/api/v1/portal/'.$organization->slug.'/dashboard')
            ->assertOk()
            ->assertJsonPath('client.email', 'ada@client.test');
    }

    public function test_cross_tenant_client_denied(): void
    {
        [, , , $client] = $this->setupPortal();
        $otherOrg = Organization::factory()->create();

        $this->actingAs($client, 'client')
            ->get(route('portal.dashboard', $otherOrg))
            ->assertForbidden();
    }
}
