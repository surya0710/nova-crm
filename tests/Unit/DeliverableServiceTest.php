<?php

namespace Tests\Unit;

use App\Models\ClientUser;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\ClientAccessService;
use App\Services\DeliverableService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliverableServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function seedContext(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');
        app(TenantContext::class)->set($organization);

        $project = app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Portal Project',
            'owner_id' => $user->id,
            'manager_id' => $user->id,
            'priority' => 'medium',
        ], $user);

        return [$user, $organization, $project];
    }

    public function test_create_and_submit_deliverable(): void
    {
        [$user, $organization, $project] = $this->seedContext();

        $service = app(DeliverableService::class);
        $deliverable = $service->create($project, [
            'title' => 'Design package',
            'description' => 'v1 assets',
        ], $user);

        $this->assertSame('draft', $deliverable->status);

        $submitted = $service->submit($deliverable, $user);
        $this->assertSame('submitted', $submitted->status);
        $this->assertNotNull($submitted->submitted_at);
    }

    public function test_client_approval_flow(): void
    {
        [$user, $organization, $project] = $this->seedContext();

        $customer = Customer::factory()->create(['organization_id' => $organization->id]);
        $project->update(['client_id' => $customer->id]);

        $client = app(ClientAccessService::class)->invite($organization, $customer, [
            'name' => 'Client Person',
            'email' => 'client@example.com',
            'password' => 'password123',
        ], $user);

        app(ClientAccessService::class)->grantProjectAccess($client, $project, null, $user);

        $deliverable = app(DeliverableService::class)->create($project, [
            'title' => 'Spec',
        ], $user);
        app(DeliverableService::class)->submit($deliverable, $user);
        $approval = app(ApprovalService::class)->createForDeliverable($deliverable->fresh(), $user);

        $this->assertSame('client_review', $approval->status);

        $approved = app(ApprovalService::class)->approve($approval, $client, 'Looks good');
        $this->assertSame('approved', $approved->status);
        $this->assertSame('approved', $deliverable->fresh()->status);
    }
}
