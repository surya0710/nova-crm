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
use Tests\TestCase;

class TaskMultiTenancyTest extends TestCase
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
            'name' => 'Tenant Project',
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

    public function test_cannot_view_other_organization_task(): void
    {
        [$userA, $organizationA] = $this->setupUserWithOrg('organization-owner');
        [$userB, $organizationB] = $this->setupUserWithOrg('organization-owner');

        $project = $this->createProject($organizationA, $userA, $userA);
        $foreignTask = $this->createTask($organizationA, $project, $userA, 'Org A Secret Task');

        $response = $this->actingAs($userB)
            ->withSession(['current_organization_id' => $organizationB->id])
            ->get(route('tasks.show', $foreignTask));

        $this->assertContains($response->status(), [403, 404]);

        $index = $this->actingAs($userB)
            ->withSession(['current_organization_id' => $organizationB->id])
            ->get(route('tasks.index'));

        $index->assertOk();
        $index->assertDontSee('Org A Secret Task');
    }
}
