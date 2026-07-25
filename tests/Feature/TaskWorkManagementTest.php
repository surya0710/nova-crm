<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectMilestoneService;
use App\Services\ProjectService;
use App\Services\TaskDefaultsService;
use App\Services\TaskService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskWorkManagementTest extends TestCase
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
            'name' => 'Work Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $overrides), $actor);
    }

    protected function createWorkTask(Organization $organization, Project $project, User $actor, array $overrides = []): Task
    {
        app(TenantContext::class)->set($organization);
        app(TaskDefaultsService::class)->seedAll($organization);

        return app(TaskService::class)->createWorkManagement(array_merge([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Work Task',
        ], $overrides), $actor);
    }

    public function test_user_can_create_work_management_task_with_project_id(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.store'), [
                'title' => 'Ship feature',
                'project_id' => $project->id,
                'priority' => 'high',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Ship feature',
            'priority' => 'high',
        ]);

        $task = Task::query()->where('title', 'Ship feature')->first();
        $this->assertNotNull($task?->task_number);
        $this->assertNotNull($task?->slug);
    }

    public function test_user_can_archive_and_restore_task(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);
        $task = $this->createWorkTask($organization, $project, $user, ['title' => 'Archive Me']);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.archive', $task))
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'is_archived' => true,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.restore', $task))
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'is_archived' => false,
        ]);
    }

    public function test_assigning_task_notifies_assignee(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $assignee = User::factory()->create();
        $organization->addMember($assignee, 'sales-executive');

        $project = $this->createProject($organization, $owner, $owner);
        $task = $this->createWorkTask($organization, $project, $owner, ['title' => 'Assign Me']);

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('tasks.assign', $task), [
                'assigned_to' => $assignee->id,
            ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $assignee->id,
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'assigned_to' => $assignee->id,
        ]);
    }

    public function test_user_can_create_subtask_with_parent_task_id(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);
        $parent = $this->createWorkTask($organization, $project, $user, ['title' => 'Parent Task']);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.store'), [
                'title' => 'Child Task',
                'project_id' => $project->id,
                'parent_task_id' => $parent->id,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Child Task',
            'parent_task_id' => $parent->id,
        ]);
    }

    public function test_user_can_link_task_to_milestone(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);

        app(TenantContext::class)->set($organization);
        $milestone = app(ProjectMilestoneService::class)->create($project, [
            'name' => 'Launch',
            'status' => 'pending',
        ], $user);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.store'), [
                'title' => 'Milestone Linked Task',
                'project_id' => $project->id,
                'milestone_id' => $milestone->id,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'organization_id' => $organization->id,
            'title' => 'Milestone Linked Task',
            'milestone_id' => $milestone->id,
        ]);

        $this->assertInstanceOf(ProjectMilestone::class, $milestone);
    }
}
