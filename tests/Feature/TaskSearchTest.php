<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\SearchService;
use App\Services\TaskDefaultsService;
use App\Services\TaskService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskSearchTest extends TestCase
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
            'name' => 'Search Project',
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

    public function test_search_service_finds_task_by_title_when_user_has_tasks_view(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $project = $this->createProject($organization, $user, $user);
        $this->createTask($organization, $project, $user, 'UniqueTaskSearchTitle');

        app(TenantContext::class)->set($organization);

        $results = app(SearchService::class)->search($user, 'UniqueTaskSearchTitle');

        $taskTitles = $results
            ->filter(fn (array $result) => $result['type'] === __('Task') || $result['label'] === __('Tasks'))
            ->pluck('title')
            ->all();

        $this->assertContains('UniqueTaskSearchTitle', $taskTitles);
    }

    public function test_search_service_excludes_tasks_without_permission(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');
        $owner = User::factory()->create();
        $organization->addMember($owner, 'organization-owner');

        $project = $this->createProject($organization, $owner, $owner);
        $this->createTask($organization, $project, $owner, 'HiddenFromHrTask');

        app(TenantContext::class)->set($organization);

        $results = app(SearchService::class)->search($user, 'HiddenFromHrTask');

        $taskTitles = $results
            ->filter(fn (array $result) => $result['type'] === __('Task') || $result['label'] === __('Tasks'))
            ->pluck('title')
            ->all();

        $this->assertNotContains('HiddenFromHrTask', $taskTitles);
    }
}
