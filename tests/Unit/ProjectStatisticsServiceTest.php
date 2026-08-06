<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\ProjectStatisticsService;
use App\Services\TaskDefaultsService;
use App\Services\TaskService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectStatisticsServiceTest extends TestCase
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
            'name' => 'Stats Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_for_project_includes_task_counts_and_velocity(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        app(TaskDefaultsService::class)->seedAll($organization);

        $project = $this->createProject($organization, $user, $user);
        $taskService = app(TaskService::class);

        $taskService->createWorkManagement([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Open task',
        ], $user);

        $closedTask = $taskService->createWorkManagement([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Closed task',
        ], $user);
        $closedTask->update(['completed_at' => now()]);

        $stats = app(ProjectStatisticsService::class)->forProject($project);

        $this->assertArrayHasKey('tasks', $stats);
        $this->assertArrayHasKey('open', $stats['tasks']);
        $this->assertArrayHasKey('closed', $stats['tasks']);
        $this->assertArrayHasKey('overdue', $stats['tasks']);
        $this->assertArrayHasKey('total', $stats['tasks']);

        $this->assertArrayHasKey('velocity', $stats);
        $this->assertArrayHasKey('period_days', $stats['velocity']);
        $this->assertArrayHasKey('completed_count', $stats['velocity']);
        $this->assertSame(14, $stats['velocity']['period_days']);
    }
}
