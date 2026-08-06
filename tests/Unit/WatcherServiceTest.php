<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectWatcher;
use App\Models\Task;
use App\Models\TaskWatcher;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TaskDefaultsService;
use App\Services\TenantContext;
use App\Services\WatcherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WatcherServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setupOrg(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');
        app(TenantContext::class)->set($organization);

        return [$user, $organization];
    }

    protected function createProject(Organization $organization, User $actor): Project
    {
        return app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Watch Project',
            'owner_id' => $actor->id,
            'manager_id' => $actor->id,
            'priority' => 'medium',
        ], $actor);
    }

    protected function makeTask(Organization $organization, User $creator, ?Project $project = null): Task
    {
        app(TaskDefaultsService::class)->seedAll($organization);

        return Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project?->id,
            'title' => 'Watch Task',
            'slug' => 'watch-task',
            'task_number' => 'TASK-0001',
            'status' => 'pending',
            'priority' => 'medium',
            'created_by' => $creator->id,
            'is_archived' => false,
            'completion_percentage' => 0,
        ]);
    }

    public function test_watch_and_unwatch_project(): void
    {
        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);
        $service = app(WatcherService::class);

        $watcher = $service->watchProject($project, $user);
        $this->assertInstanceOf(ProjectWatcher::class, $watcher);
        $this->assertDatabaseHas('project_watchers', [
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        $service->unwatchProject($project, $user);
        $this->assertDatabaseMissing('project_watchers', [
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_watch_and_unwatch_task(): void
    {
        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);
        $task = $this->makeTask($organization, $user, $project);
        $service = app(WatcherService::class);

        $watcher = $service->watchTask($task, $user);
        $this->assertInstanceOf(TaskWatcher::class, $watcher);
        $this->assertDatabaseHas('task_watchers', [
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

        $service->unwatchTask($task, $user);
        $this->assertDatabaseMissing('task_watchers', [
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);
    }
}
