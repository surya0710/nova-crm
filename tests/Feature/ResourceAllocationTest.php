<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ResourceAllocation;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceAllocationTest extends TestCase
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
            'name' => 'Web Allocation Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_user_can_create_allocation_via_web(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $project = $this->createProject($organization, $user, $user);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('resources.allocations.store'), [
                'employee_id' => $employee->id,
                'project_id' => $project->id,
                'allocation_type' => 'project',
                'allocation_percentage' => 40,
                'planned_start_date' => '2026-07-20',
                'planned_end_date' => '2026-07-31',
                'notes' => 'Web allocation',
            ]);

        $allocation = ResourceAllocation::query()
            ->where('organization_id', $organization->id)
            ->where('employee_id', $employee->id)
            ->first();

        $this->assertNotNull($allocation);
        $response->assertRedirect(route('resources.allocations.show', $allocation));
        $this->assertDatabaseHas('resource_allocations', [
            'id' => $allocation->id,
            'allocation_percentage' => 40,
            'allocation_type' => 'project',
            'project_id' => $project->id,
        ]);
    }

    public function test_user_can_update_and_release_allocation(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('resources.allocations.store'), [
                'employee_id' => $employee->id,
                'project_id' => $project->id,
                'allocation_type' => 'project',
                'allocation_percentage' => 30,
                'planned_start_date' => '2026-07-20',
                'planned_end_date' => '2026-07-31',
            ]);

        $allocation = ResourceAllocation::query()
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('resources.allocations.update', $allocation), [
                'employee_id' => $employee->id,
                'project_id' => $project->id,
                'allocation_type' => 'project',
                'allocation_percentage' => 55,
                'planned_start_date' => '2026-07-20',
                'planned_end_date' => '2026-08-07',
                'notes' => 'Updated allocation',
            ])
            ->assertRedirect(route('resources.allocations.show', $allocation));

        $this->assertDatabaseHas('resource_allocations', [
            'id' => $allocation->id,
            'allocation_percentage' => 55,
            'notes' => 'Updated allocation',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('resources.allocations.destroy', $allocation))
            ->assertRedirect(route('resources.allocations.index'));

        $this->assertDatabaseMissing('resource_allocations', ['id' => $allocation->id]);
    }
}
