<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\Dashboard\DashboardCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OrganizationSwitchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_switch_both_directions_and_context_caches_are_refreshed(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $organizationA = Organization::factory()->create(['name' => 'Organization A']);
        $organizationB = Organization::factory()->create(['name' => 'Organization B']);
        $organizationA->addMember($user, 'organization-owner');
        $organizationB->addMember($user, 'employee');

        $permissionCacheKey = "rbac:permissions:{$organizationB->id}:{$user->id}";
        Cache::put($permissionCacheKey, collect(['stale.permission']), 300);

        $dashboardCache = app(DashboardCache::class);
        $this->assertSame(
            'stale-dashboard',
            $dashboardCache->remember('switch-test', $organizationB->id, $user->id, fn () => 'stale-dashboard')
        );

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organizationA->id])
            ->post(route('organization.switch', $organizationB));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('current_organization_id', $organizationB->id);
        $response->assertSessionHas('current_organization_name', 'Organization B');
        $response->assertSessionHas('current_membership.organization_id', $organizationB->id);
        $response->assertSessionHas('current_membership.is_active', true);
        $this->assertFalse(Cache::has($permissionCacheKey));
        $this->assertSame(
            'fresh-dashboard',
            $dashboardCache->remember('switch-test', $organizationB->id, $user->id, fn () => 'fresh-dashboard')
        );

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Organization B');

        $this->post(route('organization.switch', $organizationA))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('current_organization_id', $organizationA->id)
            ->assertSessionHas('current_organization_name', 'Organization A');
    }

    public function test_switch_rejects_missing_inactive_or_disabled_membership(): void
    {
        $user = User::factory()->create();
        $current = Organization::factory()->create();
        $notAMember = Organization::factory()->create();
        $inactiveOrganization = Organization::factory()->create([
            'is_active' => false,
            'status' => 'suspended',
        ]);
        $disabledMembership = Organization::factory()->create();

        $current->addMember($user, 'organization-owner');
        $inactiveOrganization->addMember($user, 'employee');
        $disabledMembership->addMember($user, 'employee');
        $disabledMembership->users()->updateExistingPivot($user->id, ['is_active' => false]);

        foreach ([$notAMember, $inactiveOrganization, $disabledMembership] as $organization) {
            $this->actingAs($user)
                ->withSession(['current_organization_id' => $current->id])
                ->post(route('organization.switch', $organization))
                ->assertForbidden()
                ->assertSessionHas('current_organization_id', $current->id);
        }
    }

    public function test_middleware_replaces_an_ineligible_session_organization(): void
    {
        $user = User::factory()->create();
        $active = Organization::factory()->create(['name' => 'Active Organization']);
        $disabled = Organization::factory()->create();
        $active->addMember($user, 'organization-owner');
        $disabled->addMember($user, 'employee');
        $disabled->users()->updateExistingPivot($user->id, ['is_active' => false]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $disabled->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSessionHas('current_organization_id', $active->id)
            ->assertSessionHas('current_organization_name', 'Active Organization');
    }
}
