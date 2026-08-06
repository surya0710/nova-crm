<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourcePlannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_planner_capacity_and_forecast_pages_ok_with_permission(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($user)->withSession($session)
            ->get(route('resources.planner'))
            ->assertOk();

        $this->actingAs($user)->withSession($session)
            ->get(route('resources.capacity'))
            ->assertOk();

        $this->actingAs($user)->withSession($session)
            ->get(route('resources.forecast'))
            ->assertOk();
    }
}
