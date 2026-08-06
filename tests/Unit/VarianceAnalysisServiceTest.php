<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\BaselineService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use App\Services\VarianceAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VarianceAnalysisServiceTest extends TestCase
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
            'name' => 'Variance Project',
            'owner_id' => $actor->id,
            'manager_id' => $actor->id,
            'priority' => 'medium',
            'planned_end_date' => now()->addMonth()->toDateString(),
            'estimated_budget' => 5000,
            'actual_budget' => 1000,
        ], $actor);
    }

    public function test_for_project_returns_null_without_baseline(): void
    {
        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);

        $this->assertNull(app(VarianceAnalysisService::class)->forProject($project));
    }

    public function test_compare_returns_flags_and_raw(): void
    {
        Notification::fake();

        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);
        $baseline = app(BaselineService::class)->capture($project, $user);

        $analysis = app(VarianceAnalysisService::class)->compare($baseline, $project);

        $this->assertSame($baseline->id, $analysis['baseline_id']);
        $this->assertSame($project->id, $analysis['project_id']);
        $this->assertArrayHasKey('schedule', $analysis);
        $this->assertArrayHasKey('budget', $analysis);
        $this->assertArrayHasKey('scope', $analysis);
        $this->assertArrayHasKey('progress', $analysis);
        $this->assertArrayHasKey('flags', $analysis);
        $this->assertArrayHasKey('raw', $analysis);
        $this->assertIsArray($analysis['flags']);
    }

    public function test_for_project_uses_latest_baseline(): void
    {
        Notification::fake();

        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);
        app(BaselineService::class)->capture($project, $user, 'v1');
        $latest = app(BaselineService::class)->capture($project, $user, 'v2');

        $analysis = app(VarianceAnalysisService::class)->forProject($project);

        $this->assertNotNull($analysis);
        $this->assertSame($latest->id, $analysis['baseline_id']);
        $this->assertSame(2, $analysis['version']);
    }
}
