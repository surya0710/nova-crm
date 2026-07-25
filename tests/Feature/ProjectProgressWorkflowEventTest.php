<?php

namespace Tests\Feature;

use App\Events\ProgressUpdated;
use App\Events\ProjectHealthChanged;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectHealthService;
use App\Services\ProjectService;
use App\Services\TaskDefaultsService;
use App\Services\TaskService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ProjectProgressWorkflowEventTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function createProject(Organization $organization, User $owner, User $actor, array $overrides = []): Project
    {
        app(TenantContext::class)->set($organization);

        return app(ProjectService::class)->create(array_merge([
            'organization_id' => $organization->id,
            'name' => 'Workflow Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
            'planned_end_date' => now()->addMonth()->toDateString(),
        ], $overrides), $actor);
    }

    protected function createOverdueTask(Organization $organization, Project $project, User $actor): Task
    {
        app(TaskDefaultsService::class)->seedAll($organization);

        return app(TaskService::class)->createWorkManagement([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Overdue workflow task',
            'due_date' => now()->subDay()->toDateString(),
        ], $actor);
    }

    public function test_progress_updated_event_dispatched_on_create(): void
    {
        Event::fake([ProgressUpdated::class]);

        [$user, $organization] = $this->setupUserWithOrg('manager');
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.progress.store', $project), [
                'progress_percentage' => 15,
                'summary' => 'Workflow progress',
            ]);

        Event::assertDispatched(ProgressUpdated::class, function (ProgressUpdated $event) use ($organization) {
            return $event->organizationId === $organization->id
                && $event->trigger() === 'project.progress.updated';
        });
    }

    public function test_project_health_changed_event_dispatched_on_status_change(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        app(TenantContext::class)->set($organization);

        $project = $this->createProject($organization, $user, $user);
        $healthService = app(ProjectHealthService::class);

        $healthService->calculate($project, $user);

        Event::fake([ProjectHealthChanged::class]);

        $this->createOverdueTask($organization, $project, $user);
        $healthService->calculate($project->fresh(), $user);

        Event::assertDispatched(ProjectHealthChanged::class, function (ProjectHealthChanged $event) {
            return $event->trigger() === 'project.health.changed';
        });
    }
}
