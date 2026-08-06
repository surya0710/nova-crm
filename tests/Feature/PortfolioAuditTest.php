<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_auditable_creates_activity_on_portfolio_create(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('portfolios.store'), [
                'name' => 'Audited Portfolio',
                'status' => 'active',
            ])
            ->assertRedirect();

        $portfolio = Portfolio::query()
            ->where('organization_id', $organization->id)
            ->where('name', 'Audited Portfolio')
            ->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'auditable_type' => $portfolio->getMorphClass(),
            'auditable_id' => $portfolio->id,
            'event' => 'created',
            'user_id' => $user->id,
        ]);
    }
}
