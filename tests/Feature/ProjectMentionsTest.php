<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Services\ProjectService;
use App\Services\TaskDefaultsService;
use App\Services\TaskService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProjectMentionsTest extends TestCase
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
            'name' => 'Mention Feature Project',
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
            'title' => 'Mention Feature Task',
        ], $actor);
    }

    public function test_mention_in_comment_creates_project_mention_and_notification(): void
    {
        [$actor, $organization] = $this->setupUserWithOrg();
        $mentioned = User::factory()->create([
            'name' => 'Alex Mention',
            'email' => 'alex@example.com',
        ]);
        $organization->addMember($mentioned, 'manager');

        $project = $this->createProject($organization, $actor, $actor);
        $task = $this->createTask($organization, $project, $actor);

        Notification::fake();

        $this->actingAs($actor)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.comments.store', $task), [
                'comment' => 'Can you take a look @alex?',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_mentions', [
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'task_id' => $task->id,
            'mentioned_user_id' => $mentioned->id,
            'mentioned_by' => $actor->id,
        ]);

        Notification::assertSentTo($mentioned, CrmNotification::class);
    }
}
