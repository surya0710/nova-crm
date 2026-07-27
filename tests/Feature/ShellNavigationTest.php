<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserUiPreference;
use App\Services\Navigation\FavoriteWorkspacesService;
use App\Services\Navigation\NavigationService;
use App\Services\Navigation\RecentPagesService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ShellNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function tenantWithRole(string $role, string $plan = 'enterprise'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['plan' => $plan]);
        $organization->addMember($user, $role);
        app(TenantContext::class)->set($organization);

        return [
            'user' => $user,
            'organization' => $organization,
            'session' => ['current_organization_id' => $organization->id],
        ];
    }

    public function test_employee_persona_lands_on_ess_dashboard(): void
    {
        if (! Route::has('ess.dashboard')) {
            $this->markTestSkipped('ess.dashboard is not registered.');
        }

        ['user' => $user, 'organization' => $organization] = $this->tenantWithRole('employee');

        $landing = app(NavigationService::class)->resolveLandingUrl($user, $organization);

        $this->assertStringContainsString('/hrms/ess', $landing);
    }

    public function test_owner_persona_prefers_dashboard(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->tenantWithRole('organization-owner');

        $landing = app(NavigationService::class)->resolveLandingUrl($user, $organization);

        $this->assertStringContainsString('/dashboard', $landing);
    }

    public function test_workspace_switch_persists_last_workspace(): void
    {
        ['user' => $user, 'session' => $session] = $this->tenantWithRole('organization-owner');

        $this->actingAs($user)
            ->withSession($session)
            ->post(route('shell.workspace.switch'), ['workspace' => 'crm'])
            ->assertOk()
            ->assertJsonPath('workspace', 'crm');

        $prefs = UserUiPreference::query()
            ->where('user_id', $user->id)
            ->first();

        $this->assertSame('crm', $prefs?->last_workspace);
    }

    public function test_favorite_workspace_toggle_persists_in_meta(): void
    {
        ['user' => $user, 'organization' => $organization, 'session' => $session] = $this->tenantWithRole('organization-owner');

        $this->actingAs($user)
            ->withSession($session)
            ->post(route('shell.workspace-favorites.toggle'), ['workspace' => 'crm'])
            ->assertOk()
            ->assertJsonPath('favorite_workspaces.0', 'crm');

        $favorites = app(FavoriteWorkspacesService::class)->list($user, $organization);
        $this->assertTrue($favorites->contains('crm'));
    }

    public function test_recent_page_is_recorded_for_workspace_home(): void
    {
        if (! Route::has('crm.home')) {
            $this->markTestSkipped('crm.home is not registered.');
        }

        ['user' => $user, 'organization' => $organization, 'session' => $session] = $this->tenantWithRole('organization-owner');

        $this->actingAs($user)
            ->withSession($session)
            ->get(route('crm.home'))
            ->assertOk();

        $recents = app(RecentPagesService::class)->list($user, $organization);
        $this->assertTrue($recents->contains(fn ($item) => str_contains($item['label'], 'CRM')));
    }

    public function test_header_includes_workspace_switcher_markup(): void
    {
        if (! Route::has('crm.home')) {
            $this->markTestSkipped('crm.home is not registered.');
        }

        ['user' => $user, 'session' => $session] = $this->tenantWithRole('organization-owner');

        $this->actingAs($user)
            ->withSession($session)
            ->get(route('crm.home'))
            ->assertOk()
            ->assertSee('headerWorkspaceSwitcher', false);
    }

    public function test_operations_home_responds_for_task_viewer(): void
    {
        if (! Route::has('operations.home')) {
            $this->markTestSkipped('operations.home is not registered.');
        }

        ['user' => $user, 'session' => $session] = $this->tenantWithRole('organization-owner');

        $this->actingAs($user)
            ->withSession($session)
            ->get(route('operations.home'))
            ->assertOk()
            ->assertSee(__('Operations'));
    }

    public function test_global_search_returns_workspace_default_scope(): void
    {
        ['user' => $user, 'session' => $session] = $this->tenantWithRole('organization-owner');

        $this->actingAs($user)
            ->withSession($session)
            ->get(route('shell.search.index', ['q' => '']))
            ->assertOk()
            ->assertJsonStructure(['scopes', 'recent', 'default_scope']);
    }
}
