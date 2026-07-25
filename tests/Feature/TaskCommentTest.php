<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TaskDefaultsService;
use App\Services\TaskService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCommentTest extends TestCase
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
            'name' => 'Comment Project',
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
            'title' => 'Comment Task',
        ], $actor);
    }

    public function test_user_can_add_comment(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);
        $task = $this->createTask($organization, $project, $user);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.comments.store', $task), [
                'comment' => 'Looks good so far',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('task_comments', [
            'organization_id' => $organization->id,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'comment' => 'Looks good so far',
            'parent_comment_id' => null,
        ]);
    }

    public function test_user_can_add_threaded_reply(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);
        $task = $this->createTask($organization, $project, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.comments.store', $task), [
                'comment' => 'Parent comment',
            ])
            ->assertRedirect();

        $parent = TaskComment::query()->where('task_id', $task->id)->firstOrFail();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.comments.store', $task), [
                'comment' => 'Threaded reply',
                'parent_comment_id' => $parent->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $task->id,
            'comment' => 'Threaded reply',
            'parent_comment_id' => $parent->id,
        ]);
    }
}
