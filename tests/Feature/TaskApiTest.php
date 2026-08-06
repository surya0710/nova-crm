<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TaskDefaultsService;
use App\Services\TaskService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setupApiUser(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function apiHeaders(Organization $organization): array
    {
        return [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ];
    }

    protected function createProject(Organization $organization, User $owner, User $actor): Project
    {
        app(TenantContext::class)->set($organization);

        return app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'API Task Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    protected function createTask(Organization $organization, Project $project, User $actor, array $overrides = []): Task
    {
        app(TenantContext::class)->set($organization);
        app(TaskDefaultsService::class)->seedAll($organization);

        return app(TaskService::class)->createWorkManagement(array_merge([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'API Task',
        ], $overrides), $actor);
    }

    public function test_api_index_returns_tasks(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');
        $project = $this->createProject($organization, $user, $user);
        $this->createTask($organization, $project, $user, ['title' => 'Visible API Task']);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/tasks', $this->apiHeaders($organization));

        $response->assertOk();
        $response->assertJsonFragment(['title' => 'Visible API Task']);
    }

    public function test_api_store_creates_task(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');
        $project = $this->createProject($organization, $user, $user);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/tasks', [
            'title' => 'Created Via API',
            'project_id' => $project->id,
            'priority' => 'high',
        ], $this->apiHeaders($organization));

        $response->assertCreated();
        $response->assertJsonFragment(['title' => 'Created Via API']);

        $this->assertDatabaseHas('tasks', [
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Created Via API',
            'priority' => 'high',
        ]);
    }

    public function test_api_show_returns_task(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');
        $project = $this->createProject($organization, $user, $user);
        $task = $this->createTask($organization, $project, $user, ['title' => 'Show Me']);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/tasks/'.$task->id, $this->apiHeaders($organization));

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $task->id,
            'title' => 'Show Me',
        ]);
    }

    public function test_api_without_permission_returns_forbidden(): void
    {
        [$user, $organization] = $this->setupApiUser('hr');

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/tasks', $this->apiHeaders($organization))
            ->assertForbidden();
    }
}
