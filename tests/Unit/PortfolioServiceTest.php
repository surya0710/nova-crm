<?php

namespace Tests\Unit;

use App\Events\PortfolioCreated;
use App\Events\PortfolioDeleted;
use App\Events\PortfolioUpdated;
use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\Project;
use App\Models\User;
use App\Services\PortfolioService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PortfolioServiceTest extends TestCase
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
            'name' => 'Portfolio Project',
            'owner_id' => $actor->id,
            'manager_id' => $actor->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_create_portfolio(): void
    {
        Event::fake([PortfolioCreated::class]);

        [$user, $organization] = $this->setupOrg();
        $service = app(PortfolioService::class);

        $portfolio = $service->create([
            'organization_id' => $organization->id,
            'name' => 'Growth Portfolio',
            'description' => 'Strategic bets',
            'status' => 'active',
        ], $user);

        $this->assertInstanceOf(Portfolio::class, $portfolio);
        $this->assertSame('Growth Portfolio', $portfolio->name);
        $this->assertSame($organization->id, $portfolio->organization_id);
        $this->assertNotEmpty($portfolio->code);
        Event::assertDispatched(PortfolioCreated::class);
    }

    public function test_attach_and_detach_project(): void
    {
        Event::fake([PortfolioUpdated::class]);

        [$user, $organization] = $this->setupOrg();
        $service = app(PortfolioService::class);
        $project = $this->createProject($organization, $user);

        $portfolio = $service->create([
            'organization_id' => $organization->id,
            'name' => 'Attach Portfolio',
        ], $user);

        Event::fake([PortfolioUpdated::class]);

        $service->attachProject($portfolio, $project, $user);
        $this->assertDatabaseHas('portfolio_projects', [
            'portfolio_id' => $portfolio->id,
            'project_id' => $project->id,
        ]);

        $service->detachProject($portfolio, $project, $user);
        $this->assertDatabaseMissing('portfolio_projects', [
            'portfolio_id' => $portfolio->id,
            'project_id' => $project->id,
        ]);

        Event::assertDispatched(PortfolioUpdated::class);
    }

    public function test_archive_and_delete_portfolio(): void
    {
        [$user, $organization] = $this->setupOrg();
        $service = app(PortfolioService::class);

        $portfolio = $service->create([
            'organization_id' => $organization->id,
            'name' => 'Archive Me',
        ], $user);

        Event::fake([PortfolioUpdated::class, PortfolioDeleted::class]);

        $archived = $service->archive($portfolio, $user);
        $this->assertNotNull($archived->archived_at);
        $this->assertSame('archived', $archived->status);

        $service->delete($archived, $user);
        $this->assertDatabaseMissing('portfolios', ['id' => $portfolio->id]);
        Event::assertDispatched(PortfolioDeleted::class);
    }

    public function test_list_filters_by_search(): void
    {
        [$user, $organization] = $this->setupOrg();
        $service = app(PortfolioService::class);

        $service->create([
            'organization_id' => $organization->id,
            'name' => 'Alpha Unique Portfolio',
        ], $user);
        $service->create([
            'organization_id' => $organization->id,
            'name' => 'Beta Other',
        ], $user);

        $results = $service->list($organization, ['search' => 'Alpha Unique']);
        $this->assertCount(1, $results);
        $this->assertSame('Alpha Unique Portfolio', $results->first()->name);
    }
}
