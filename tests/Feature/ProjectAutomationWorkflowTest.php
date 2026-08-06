<?php

namespace Tests\Feature;

use App\Events\ProjectCollaborationUpdated;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ProjectAutomationWorkflowTest extends TestCase
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
            'name' => 'Workflow Collab Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_pinning_dispatches_collaboration_updated_event(): void
    {
        Event::fake([ProjectCollaborationUpdated::class]);

        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.collaboration.pins.store', $project), [
                'source_type' => 'progress_update',
                'source_id' => 7,
                'title' => 'Pinned for workflow',
            ])
            ->assertRedirect();

        Event::assertDispatched(ProjectCollaborationUpdated::class, function (ProjectCollaborationUpdated $event) use ($project, $user) {
            return (int) $event->payload['project_id'] === (int) $project->id
                && (int) $event->payload['actor_id'] === (int) $user->id
                && ($event->payload['action'] ?? null) === 'pinned';
        });
    }
}
