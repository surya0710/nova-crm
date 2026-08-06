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

class TaskNotificationTest extends TestCase
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
            'name' => 'Notify Task Project',
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
            'title' => 'Notify Task',
        ], $actor);
    }

    public function test_assigning_task_dispatches_notification_to_assignee(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $assignee = User::factory()->create();
        $organization->addMember($assignee, 'sales-executive');

        $project = $this->createProject($organization, $owner, $owner);
        $task = $this->createTask($organization, $project, $owner);

        Notification::fake();

        app(TenantContext::class)->set($organization);
        app(TaskService::class)->assign($task, $assignee, $owner);

        Notification::assertSentTo($assignee, CrmNotification::class);
    }
}
