<?php

namespace Tests\Feature;

use App\Events\ProjectCreated;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ProjectWorkflowEventTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_project_created_event_dispatched_on_create(): void
    {
        Event::fake([ProjectCreated::class]);

        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.store'), [
                'name' => 'Workflow Project',
                'owner_id' => $user->id,
                'manager_id' => $user->id,
                'priority' => 'medium',
            ]);

        Event::assertDispatched(ProjectCreated::class, function (ProjectCreated $event) use ($organization) {
            return $event->organizationId === $organization->id
                && $event->trigger() === 'project.created';
        });
    }
}
