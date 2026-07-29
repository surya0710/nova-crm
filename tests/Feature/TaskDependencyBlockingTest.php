<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TaskDependencyService;
use App\Services\TaskService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaskDependencyBlockingTest extends TestCase
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
            'name' => 'Dependency Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_cannot_complete_task_blocked_by_open_predecessor(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $project = $this->createProject($organization, $user, $user);

        $blocker = Task::factory()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Task A',
            'status' => 'pending',
            'assigned_to' => $user->id,
        ]);

        $blocked = Task::factory()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Task B',
            'status' => 'pending',
            'assigned_to' => $user->id,
        ]);

        app(TaskDependencyService::class)->create($blocker, $blocked, [
            'dependency_type' => 'finish_to_start',
        ], $user);

        $this->expectException(ValidationException::class);

        app(TaskService::class)->complete($blocked, $user);
    }

    public function test_dependencies_page_shows_blocked_by_summary(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $project = $this->createProject($organization, $user, $user);

        $blocker = Task::factory()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Task A',
            'status' => 'pending',
            'assigned_to' => $user->id,
        ]);

        $blocked = Task::factory()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Task B',
            'status' => 'pending',
            'assigned_to' => $user->id,
        ]);

        app(TaskDependencyService::class)->create($blocker, $blocked, [
            'dependency_type' => 'finish_to_start',
        ], $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('tasks.dependencies.index', $blocked))
            ->assertOk()
            ->assertSee('Blocked By')
            ->assertSee('Task A');
    }
}
