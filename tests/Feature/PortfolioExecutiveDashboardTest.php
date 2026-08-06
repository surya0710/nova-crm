<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioExecutiveDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_portfolio_executive_dashboard_ok(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        Portfolio::factory()->create([
            'organization_id' => $organization->id,
            'owner_id' => $user->id,
            'name' => 'Exec View Portfolio',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('portfolios.executive'))
            ->assertOk()
            ->assertSee('Exec View Portfolio');
    }

    public function test_hr_forbidden_without_executive_permission(): void
    {
        [$hrUser, $organization] = $this->setupUserWithOrg('hr');

        $this->actingAs($hrUser)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('portfolios.executive'))
            ->assertForbidden();
    }
}
