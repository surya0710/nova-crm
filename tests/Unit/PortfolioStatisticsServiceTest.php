<?php

namespace Tests\Unit;

use App\Events\PortfolioHealthUpdated;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\PortfolioService;
use App\Services\PortfolioStatisticsService;
use App\Services\ProjectService;
use App\Services\RiskManagementService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PortfolioStatisticsServiceTest extends TestCase
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
            'name' => 'Stats Project',
            'owner_id' => $actor->id,
            'manager_id' => $actor->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_for_portfolio_returns_stats_payload(): void
    {
        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);
        $portfolio = app(PortfolioService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Stats Portfolio',
            'project_ids' => [$project->id],
        ], $user);

        app(RiskManagementService::class)->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Open risk',
            'probability' => 3,
            'impact' => 3,
        ], $user);

        $stats = app(PortfolioStatisticsService::class)->forPortfolio($portfolio->fresh('projects'));

        $this->assertSame($portfolio->id, $stats['portfolio_id']);
        $this->assertSame(1, $stats['project_count']);
        $this->assertArrayHasKey('average_completion_percentage', $stats);
        $this->assertArrayHasKey('health', $stats);
        $this->assertArrayHasKey('budget', $stats);
        $this->assertArrayHasKey('projects_by_status', $stats);
        $this->assertArrayHasKey('risk_score', $stats);
    }

    public function test_dispatch_health_event_when_requested(): void
    {
        Event::fake([PortfolioHealthUpdated::class]);

        [$user, $organization] = $this->setupOrg();
        $portfolio = app(PortfolioService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Health Event Portfolio',
        ], $user);

        app(PortfolioStatisticsService::class)->forPortfolio($portfolio, $user, true);

        Event::assertDispatched(PortfolioHealthUpdated::class);
    }

    public function test_average_progress_empty_collection(): void
    {
        $this->assertSame(0.0, app(PortfolioStatisticsService::class)->averageProgress(collect()));
    }
}
