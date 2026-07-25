<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioManagementTest extends TestCase
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
            'name' => 'Portfolio Mgmt Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_web_crud_for_portfolios(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('portfolios.store'), [
                'name' => 'Strategic Portfolio',
                'status' => 'active',
                'color' => '#4f46e5',
            ])
            ->assertRedirect();

        $portfolio = Portfolio::query()
            ->where('organization_id', $organization->id)
            ->where('name', 'Strategic Portfolio')
            ->firstOrFail();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('portfolios.show', $portfolio))
            ->assertOk()
            ->assertSee('Strategic Portfolio');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('portfolios.update', $portfolio), [
                'name' => 'Strategic Portfolio Updated',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('portfolios', [
            'id' => $portfolio->id,
            'name' => 'Strategic Portfolio Updated',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('portfolios.destroy', $portfolio))
            ->assertRedirect(route('portfolios.index'));

        $this->assertDatabaseMissing('portfolios', ['id' => $portfolio->id]);
    }

    public function test_attach_and_detach_project(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);

        $portfolio = Portfolio::factory()->create([
            'organization_id' => $organization->id,
            'owner_id' => $user->id,
            'name' => 'Attach Portfolio',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('portfolios.projects.attach', $portfolio), [
                'project_id' => $project->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('portfolio_projects', [
            'portfolio_id' => $portfolio->id,
            'project_id' => $project->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('portfolios.projects.detach', [$portfolio, $project]))
            ->assertRedirect();

        $this->assertDatabaseMissing('portfolio_projects', [
            'portfolio_id' => $portfolio->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_archive_portfolio(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $portfolio = Portfolio::factory()->create([
            'organization_id' => $organization->id,
            'owner_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('portfolios.archive', $portfolio))
            ->assertRedirect();

        $this->assertNotNull($portfolio->fresh()->archived_at);
    }
}
