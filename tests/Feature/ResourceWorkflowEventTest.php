<?php

namespace Tests\Feature;

use App\Events\ResourceAllocated;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ResourceWorkflowEventTest extends TestCase
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
            'name' => 'Workflow Allocation Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_resource_allocated_dispatched_on_create(): void
    {
        Event::fake([ResourceAllocated::class]);

        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('resources.allocations.store'), [
                'employee_id' => $employee->id,
                'project_id' => $project->id,
                'allocation_type' => 'project',
                'allocation_percentage' => 25,
                'planned_start_date' => '2026-07-20',
                'planned_end_date' => '2026-07-31',
            ]);

        Event::assertDispatched(ResourceAllocated::class, function (ResourceAllocated $event) use ($organization) {
            return $event->organizationId === $organization->id
                && $event->trigger() === 'resource.allocated';
        });
    }
}
