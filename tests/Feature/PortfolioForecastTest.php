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

class PortfolioForecastTest extends TestCase
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
            'name' => 'Forecast Feature Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
            'planned_end_date' => now()->addWeeks(3)->toDateString(),
        ], $actor);
    }

    public function test_forecast_index_and_show(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);

        $portfolio = Portfolio::factory()->create([
            'organization_id' => $organization->id,
            'owner_id' => $user->id,
            'name' => 'Forecastable Portfolio',
        ]);
        $portfolio->projects()->attach($project->id);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('portfolios.forecasts.index'))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('portfolios.forecasts.show', $portfolio))
            ->assertOk()
            ->assertSee('Forecastable Portfolio');
    }
}
