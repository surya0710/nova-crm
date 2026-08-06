<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_hr_is_forbidden_on_planner(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('resources.planner'))
            ->assertForbidden();
    }
}
