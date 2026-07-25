<?php

namespace Tests\Unit;

use App\Events\ForecastGenerated;
use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\Project;
use App\Models\User;
use App\Services\ForecastService;
use App\Services\PortfolioService;
use App\Services\ProjectService;
use App\Services\RiskManagementService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ForecastServiceTest extends TestCase
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
            'name' => 'Forecast Project',
            'owner_id' => $actor->id,
            'manager_id' => $actor->id,
            'priority' => 'medium',
            'planned_end_date' => now()->addWeeks(2)->toDateString(),
        ], $actor);
    }

    public function test_for_project_returns_forecast_payload(): void
    {
        Event::fake([ForecastGenerated::class]);

        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);

        $payload = app(ForecastService::class)->forProject($project, $user);

        $this->assertSame('project', $payload['subject']);
        $this->assertSame($project->id, $payload['project_id']);
        $this->assertArrayHasKey('likely_delay', $payload);
        $this->assertArrayHasKey('budget_overrun', $payload);
        $this->assertArrayHasKey('risk_forecast', $payload);
        Event::assertDispatched(ForecastGenerated::class);
    }

    public function test_for_portfolio_aggregates_projects(): void
    {
        Event::fake([ForecastGenerated::class]);

        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);
        $portfolio = app(PortfolioService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Forecast Portfolio',
            'project_ids' => [$project->id],
        ], $user);

        app(RiskManagementService::class)->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Forecast risk',
            'probability' => 4,
            'impact' => 4,
        ], $user);

        $payload = app(ForecastService::class)->forPortfolio($portfolio->fresh('projects'), $user);

        $this->assertSame('portfolio', $payload['subject']);
        $this->assertSame($portfolio->id, $payload['portfolio_id']);
        $this->assertArrayHasKey('projects', $payload);
        $this->assertArrayHasKey('portfolio_capacity', $payload);
        Event::assertDispatched(ForecastGenerated::class);
    }

    public function test_risk_forecast_outlook_keys(): void
    {
        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);

        $forecast = app(ForecastService::class)->riskForecast($project);
        $this->assertArrayHasKey('score', $forecast);
        $this->assertArrayHasKey('open_count', $forecast);
        $this->assertArrayHasKey('outlook', $forecast);
    }
}
