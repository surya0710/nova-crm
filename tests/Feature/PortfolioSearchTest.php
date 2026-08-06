<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\Program;
use App\Models\ProjectRisk;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\SearchService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_search_finds_portfolio_by_name(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        Portfolio::factory()->create([
            'organization_id' => $organization->id,
            'owner_id' => $user->id,
            'name' => 'UniquePortfolioSearchPhrase',
        ]);

        $results = app(SearchService::class)->search($user, 'UniquePortfolioSearchPhrase');
        $titles = $results
            ->filter(fn (array $result) => $result['type'] === __('Portfolio'))
            ->pluck('title')
            ->all();

        $this->assertContains('UniquePortfolioSearchPhrase', $titles);
    }

    public function test_search_finds_program_and_risk(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        Program::factory()->create([
            'organization_id' => $organization->id,
            'manager_id' => $user->id,
            'portfolio_id' => null,
            'name' => 'UniqueProgramSearchPhrase',
        ]);

        $project = app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Search Risk Project',
            'owner_id' => $user->id,
            'manager_id' => $user->id,
            'priority' => 'medium',
        ], $user);

        ProjectRisk::factory()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'owner_id' => $user->id,
            'title' => 'UniqueRiskSearchPhrase',
        ]);

        $programTitles = app(SearchService::class)->search($user, 'UniqueProgramSearchPhrase')
            ->filter(fn (array $result) => $result['type'] === __('Program'))
            ->pluck('title')
            ->all();
        $this->assertContains('UniqueProgramSearchPhrase', $programTitles);

        $riskTitles = app(SearchService::class)->search($user, 'UniqueRiskSearchPhrase')
            ->filter(fn (array $result) => $result['type'] === __('Risk'))
            ->pluck('title')
            ->all();
        $this->assertContains('UniqueRiskSearchPhrase', $riskTitles);
    }

    public function test_search_excludes_portfolio_without_permission(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $hrUser = User::factory()->create();
        $organization->addMember($hrUser, 'hr');
        app(TenantContext::class)->set($organization);

        Portfolio::factory()->create([
            'organization_id' => $organization->id,
            'owner_id' => $owner->id,
            'name' => 'HiddenPortfolioSearchPhrase',
        ]);

        $results = app(SearchService::class)->search($hrUser, 'HiddenPortfolioSearchPhrase');
        $titles = $results
            ->filter(fn (array $result) => $result['type'] === __('Portfolio'))
            ->pluck('title')
            ->all();

        $this->assertNotContains('HiddenPortfolioSearchPhrase', $titles);
    }
}
