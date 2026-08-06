<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\ProgressUpdate;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectHealthService;
use App\Services\ProjectService;
use App\Services\TaskDefaultsService;
use App\Services\TaskService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectHealthServiceTest extends TestCase
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
            'name' => 'Health Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $overrides), $actor);
    }

    protected function createTask(Organization $organization, Project $project, User $actor, array $overrides = []): Task
    {
        app(TenantContext::class)->set($organization);
        app(TaskDefaultsService::class)->seedAll($organization);

        return app(TaskService::class)->createWorkManagement(array_merge([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Health Task',
        ], $overrides), $actor);
    }

    public function test_calculate_completion_percentage_uses_configured_weights(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $project = $this->createProject($organization, $user, $user, [
            'completion_percentage' => 80,
            'start_date' => now()->subDays(10)->toDateString(),
            'planned_end_date' => now()->addDays(20)->toDateString(),
        ]);

        $this->createTask($organization, $project, $user, ['completion_percentage' => 100]);
        $this->createTask($organization, $project, $user, ['completion_percentage' => 0]);

        ProjectMilestone::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'name' => 'M1',
            'status' => 'completed',
            'sequence' => 1,
        ]);
        ProjectMilestone::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'name' => 'M2',
            'status' => 'pending',
            'sequence' => 2,
        ]);

        ProgressUpdate::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'updated_by' => $user->id,
            'progress_percentage' => 60,
            'summary' => 'Manual progress',
        ]);

        $service = app(ProjectHealthService::class);
        $project->load('progressUpdates');

        // task 50% (avg 50) * 0.5 + milestone 50% * 0.3 + manual 60% * 0.2 = 25 + 15 + 12 = 52
        $this->assertSame(52, $service->calculateCompletionPercentage($project));
    }

    public function test_determine_health_status_on_track_for_healthy_project(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $project = $this->createProject($organization, $user, $user, [
            'planned_end_date' => now()->addMonth()->toDateString(),
        ]);

        $service = app(ProjectHealthService::class);
        $metrics = [
            'overdue_tasks_count' => 0,
            'delayed_milestones_count' => 0,
            'schedule_variance_days' => 0,
        ];

        $this->assertSame('on_track', $service->determineHealthStatus($project, $metrics));
    }

    public function test_determine_health_status_archived_when_project_archived(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $project = $this->createProject($organization, $user, $user);
        $project->update(['is_archived' => true]);

        $service = app(ProjectHealthService::class);

        $this->assertSame('archived', $service->determineHealthStatus($project, []));
    }

    public function test_determine_health_status_at_risk_with_overdue_tasks(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $project = $this->createProject($organization, $user, $user, [
            'planned_end_date' => now()->addMonth()->toDateString(),
        ]);

        $this->createTask($organization, $project, $user, [
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $service = app(ProjectHealthService::class);
        $overdue = $service->detectOverdueTasks($project);
        $metrics = [
            'overdue_tasks_count' => $overdue->count(),
            'delayed_milestones_count' => 0,
            'schedule_variance_days' => 0,
        ];

        $this->assertSame('at_risk', $service->determineHealthStatus($project->fresh(), $metrics));
    }

    public function test_determine_health_status_delayed_with_many_overdue_tasks(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $project = $this->createProject($organization, $user, $user, [
            'planned_end_date' => now()->addMonth()->toDateString(),
        ]);

        foreach (range(1, 3) as $i) {
            $this->createTask($organization, $project, $user, [
                'title' => "Overdue {$i}",
                'due_date' => now()->subDays($i)->toDateString(),
            ]);
        }

        $service = app(ProjectHealthService::class);
        $overdue = $service->detectOverdueTasks($project);
        $metrics = [
            'overdue_tasks_count' => $overdue->count(),
            'delayed_milestones_count' => 0,
            'schedule_variance_days' => 0,
        ];

        $this->assertGreaterThanOrEqual(3, $metrics['overdue_tasks_count']);
        $this->assertSame('delayed', $service->determineHealthStatus($project->fresh(), $metrics));
    }

    public function test_calculate_creates_health_snapshot(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $project = $this->createProject($organization, $user, $user);

        $service = app(ProjectHealthService::class);
        $snapshot = $service->calculate($project, $user);

        $this->assertDatabaseHas('project_health_snapshots', [
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'id' => $snapshot->id,
            'health_status' => $snapshot->health_status,
        ]);

        $this->assertNotNull($snapshot->calculated_at);
        $this->assertIsArray($snapshot->metadata);
    }
}
