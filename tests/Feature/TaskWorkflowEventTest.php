<?php

namespace Tests\Feature;

use App\Events\TaskCreated;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TaskWorkflowEventTest extends TestCase
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
            'name' => 'Workflow Task Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_task_created_event_dispatched_on_create(): void
    {
        Event::fake([TaskCreated::class]);

        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.store'), [
                'title' => 'Workflow Task',
                'project_id' => $project->id,
                'priority' => 'medium',
            ]);

        Event::assertDispatched(TaskCreated::class, function (TaskCreated $event) use ($organization) {
            return $event->organizationId === $organization->id
                && $event->trigger() === 'task.created';
        });
    }
}
