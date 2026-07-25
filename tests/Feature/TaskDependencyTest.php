<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDependency;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TaskDefaultsService;
use App\Services\TaskDependencyService;
use App\Services\TaskService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaskDependencyTest extends TestCase
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

    protected function createTask(Organization $organization, Project $project, User $actor, string $title): Task
    {
        app(TenantContext::class)->set($organization);
        app(TaskDefaultsService::class)->seedAll($organization);

        return app(TaskService::class)->createWorkManagement([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => $title,
        ], $actor);
    }

    public function test_user_can_create_dependency(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);
        $predecessor = $this->createTask($organization, $project, $user, 'Predecessor');
        $successor = $this->createTask($organization, $project, $user, 'Successor');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.dependencies.store', $successor), [
                'predecessor_task_id' => $predecessor->id,
                'dependency_type' => 'finish_to_start',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('task_dependencies', [
            'organization_id' => $organization->id,
            'predecessor_task_id' => $predecessor->id,
            'successor_task_id' => $successor->id,
            'dependency_type' => 'finish_to_start',
        ]);
    }

    public function test_circular_dependency_is_rejected(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);
        $taskA = $this->createTask($organization, $project, $user, 'Task A');
        $taskB = $this->createTask($organization, $project, $user, 'Task B');

        $service = app(TaskDependencyService::class);
        $service->create($taskA, $taskB, ['dependency_type' => 'finish_to_start'], $user);

        $this->expectException(ValidationException::class);
        $service->create($taskB, $taskA, ['dependency_type' => 'finish_to_start'], $user);
    }

    public function test_dependency_already_exists_is_rejected(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);
        $taskA = $this->createTask($organization, $project, $user, 'Task A');
        $taskB = $this->createTask($organization, $project, $user, 'Task B');

        $service = app(TaskDependencyService::class);
        $created = $service->create($taskA, $taskB, ['dependency_type' => 'finish_to_start'], $user);

        $this->assertInstanceOf(TaskDependency::class, $created);

        $this->expectException(ValidationException::class);
        $service->create($taskA, $taskB, ['dependency_type' => 'finish_to_start'], $user);
    }
}
