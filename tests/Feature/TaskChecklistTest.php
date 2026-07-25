<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ChecklistService;
use App\Services\ProjectService;
use App\Services\TaskDefaultsService;
use App\Services\TaskService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskChecklistTest extends TestCase
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
            'name' => 'Checklist Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    protected function createTask(Organization $organization, Project $project, User $actor): Task
    {
        app(TenantContext::class)->set($organization);
        app(TaskDefaultsService::class)->seedAll($organization);

        return app(TaskService::class)->createWorkManagement([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Checklist Task',
        ], $actor);
    }

    public function test_user_can_create_checklist_item(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);
        $task = $this->createTask($organization, $project, $user);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.checklists.store', $task), [
                'title' => 'Write tests',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('task_checklists', [
            'organization_id' => $organization->id,
            'task_id' => $task->id,
            'title' => 'Write tests',
            'is_completed' => false,
        ]);
    }

    public function test_completing_checklist_item_updates_task_progress(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);
        $task = $this->createTask($organization, $project, $user);

        $checklists = app(ChecklistService::class);
        $first = $checklists->create($task, ['title' => 'Step 1'], $user);
        $checklists->create($task, ['title' => 'Step 2'], $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('tasks.checklists.complete', [$task, $first]))
            ->assertRedirect();

        $this->assertDatabaseHas('task_checklists', [
            'id' => $first->id,
            'is_completed' => true,
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'completion_percentage' => 50,
        ]);
    }
}
