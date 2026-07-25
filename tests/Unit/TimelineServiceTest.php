<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use App\Services\TimelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimelineServiceTest extends TestCase
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
            'name' => 'Timeline Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
            'start_date' => now()->subWeek()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
        ], $overrides), $actor);
    }

    public function test_build_returns_expected_structure(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $project = $this->createProject($organization, $user, $user);
        $timeline = app(TimelineService::class)->build($project);

        $this->assertArrayHasKey('project', $timeline);
        $this->assertArrayHasKey('milestones', $timeline);
        $this->assertArrayHasKey('tasks', $timeline);
        $this->assertArrayHasKey('dependencies', $timeline);
        $this->assertArrayHasKey('resource_allocations', $timeline);

        $this->assertSame($project->id, $timeline['project']['id']);
        $this->assertSame($project->name, $timeline['project']['name']);
        $this->assertIsArray($timeline['milestones']);
        $this->assertIsArray($timeline['tasks']);
    }

    public function test_gantt_returns_expected_structure(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $project = $this->createProject($organization, $user, $user);
        $project->loadMissing(['milestones', 'status']);

        $gantt = app(TimelineService::class)->gantt($project);

        $this->assertIsArray($gantt);
        $this->assertNotEmpty($gantt);

        $projectBar = collect($gantt)->firstWhere('type', 'project');
        $this->assertNotNull($projectBar);
        $this->assertArrayHasKey('id', $projectBar);
        $this->assertArrayHasKey('name', $projectBar);
        $this->assertArrayHasKey('start', $projectBar);
        $this->assertArrayHasKey('end', $projectBar);
        $this->assertArrayHasKey('progress', $projectBar);
        $this->assertArrayHasKey('dependencies', $projectBar);
        $this->assertArrayHasKey('color', $projectBar);
    }
}
