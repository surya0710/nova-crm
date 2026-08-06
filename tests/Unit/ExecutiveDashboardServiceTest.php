<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ExecutiveDashboardService;
use App\Services\PortfolioService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutiveDashboardServiceTest extends TestCase
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
            'name' => 'Exec Project',
            'owner_id' => $actor->id,
            'manager_id' => $actor->id,
            'priority' => 'medium',
            'planned_end_date' => now()->addMonth()->toDateString(),
        ], $actor);
    }

    public function test_for_organization_returns_executive_payload(): void
    {
        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);

        app(PortfolioService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Exec Portfolio',
            'project_ids' => [$project->id],
        ], $user);

        $dashboard = app(ExecutiveDashboardService::class)->forOrganization($organization, $user);

        $this->assertSame($organization->id, $dashboard['organization_id']);
        $this->assertArrayHasKey('portfolio_health', $dashboard);
        $this->assertArrayHasKey('progress', $dashboard);
        $this->assertArrayHasKey('budget_status', $dashboard);
        $this->assertArrayHasKey('portfolios', $dashboard);
        $this->assertNotEmpty($dashboard['portfolios']);
    }
}
