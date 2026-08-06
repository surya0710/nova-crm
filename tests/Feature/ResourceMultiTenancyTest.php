<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\ResourceAllocationService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceMultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function createProject(Organization $organization, User $owner, User $actor): Project
    {
        app(TenantContext::class)->set($organization);

        return app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Tenant Allocation Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_cannot_access_other_organization_allocation(): void
    {
        [$userA, $organizationA] = $this->setupUserWithOrg('organization-owner');
        [$userB, $organizationB] = $this->setupUserWithOrg('organization-owner');

        $employee = Employee::factory()->create(['organization_id' => $organizationA->id]);
        $project = $this->createProject($organizationA, $userA, $userA);

        app(TenantContext::class)->set($organizationA);
        $allocation = app(ResourceAllocationService::class)->create([
            'organization_id' => $organizationA->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'allocation_type' => 'project',
            'allocation_percentage' => 40,
            'planned_start_date' => '2026-07-20',
            'planned_end_date' => '2026-07-31',
            'notes' => 'Org A Secret Allocation',
        ], $userA);

        $response = $this->actingAs($userB)
            ->withSession(['current_organization_id' => $organizationB->id])
            ->get(route('resources.allocations.show', $allocation));

        $this->assertContains($response->status(), [403, 404]);

        $index = $this->actingAs($userB)
            ->withSession(['current_organization_id' => $organizationB->id])
            ->get(route('resources.allocations.index'));

        $index->assertOk();
        $index->assertDontSee('Org A Secret Allocation');
    }
}
