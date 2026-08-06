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

    public function test_employee_persona_lands_on_hrms_workspace(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->tenantWithRole('employee');

        $nav = app(NavigationService::class);
        $landing = $nav->resolveLandingUrl($user, $organization);

        $this->assertTrue(
            str_contains($landing, '/hrms') || str_contains($landing, '/ess'),
            "Employee should land in HRMS/ESS, got: {$landing}"
        );
        $path = parse_url($landing, PHP_URL_PATH) ?? $landing;
        $this->assertStringNotContainsString('/dashboard', $path);
    }

    public function test_owner_persona_lands_on_crm_workspace(): void
    {
        if (! Route::has('crm.home')) {
            $this->markTestSkipped('crm.home is not registered.');
        }

        ['user' => $user, 'organization' => $organization] = $this->tenantWithRole('organization-owner');

        $landing = app(NavigationService::class)->resolveLandingUrl($user, $organization);

        $this->assertStringContainsString('/crm', $landing);
        $this->assertStringNotContainsString('/dashboard', parse_url($landing, PHP_URL_PATH) ?? $landing);
    }

    public function test_user_default_workspace_overrides_persona_and_last_workspace(): void
    {
        if (! Route::has('projects.home') || ! Route::has('crm.home')) {
            $this->markTestSkipped('Workspace homes are not registered.');
        }

        ['user' => $user, 'organization' => $organization] = $this->tenantWithRole('organization-owner');

        $prefs = app(\App\Services\Theme\ThemeService::class)->preferencesFor($user, $organization);
        $prefs->update([
            'default_workspace' => 'projects',
            'last_workspace' => 'crm',
        ]);

        $landing = app(NavigationService::class)->resolveLandingUrl($user, $organization, $prefs->fresh());

        $this->assertStringContainsString('/projects', $landing);
    }

    public function test_organization_default_workspace_applies_for_owner_without_persona_conflict(): void
    {
        if (! Route::has('projects.home')) {
            $this->markTestSkipped('projects.home is not registered.');
        }

        ['user' => $user, 'organization' => $organization] = $this->tenantWithRole('organization-owner');

        // Owner persona maps to CRM; org default projects should win only when
        // we clear the persona workspace mapping for this assertion by using a
        // user without CRM… Instead verify org default after persona is unavailable:
        $settings = is_array($organization->settings) ? $organization->settings : [];
        $settings['default_workspace'] = 'projects';
        $settings['workspace_visibility'] = array_merge(
            $settings['workspace_visibility'] ?? [],
            ['crm' => false, 'home' => false, 'hr' => false, 'administration' => false, 'analytics' => false]
        );
        $organization->settings = $settings;
        $organization->save();

        $landing = app(NavigationService::class)->resolveLandingUrl($user, $organization);

        $this->assertStringContainsString('/projects', $landing);
    }

    public function test_last_workspace_is_fallback_when_no_defaults(): void
    {
        if (! Route::has('projects.home')) {
            $this->markTestSkipped('projects.home is not registered.');
        }

        ['user' => $user, 'organization' => $organization] = $this->tenantWithRole('organization-owner');

        // Clear persona path by forcing unavailable persona workspace mapping via last only:
        // Owner persona maps to crm; set last to projects and clear org default — persona still wins.
        // To verify last-as-fallback, set persona workspace unavailable: hide crm from visibility.
        $settings = is_array($organization->settings) ? $organization->settings : [];
        $settings['workspace_visibility'] = [
            'crm' => false,
            'projects' => true,
            'hr' => false,
            'home' => false,
            'administration' => false,
            'analytics' => false,
            'operations' => false,
            'marketing' => false,
            'recruitment' => false,
        ];
        $organization->settings = $settings;
        $organization->save();

        $prefs = app(\App\Services\Theme\ThemeService::class)->preferencesFor($user, $organization);
        $prefs->update(['last_workspace' => 'projects', 'default_workspace' => null]);

        $landing = app(NavigationService::class)->resolveLandingUrl($user, $organization, $prefs->fresh());

        $this->assertStringContainsString('/projects', $landing);
    }

    public function test_shell_quick_actions_are_workspace_scoped_with_overflow(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->tenantWithRole('organization-owner');

        $actions = app(NavigationService::class)->quickActions($user, $organization, 'crm');

        $this->assertArrayHasKey('primary', $actions);
        $this->assertArrayHasKey('overflow', $actions);
        $this->assertArrayHasKey('all', $actions);
        $this->assertLessThanOrEqual(5, count($actions['primary']));
        $this->assertNotEmpty($actions['primary']);

        $labels = collect($actions['all'])->pluck('label')->implode(' ');
        $this->assertStringContainsString('Lead', $labels);
        $this->assertStringNotContainsString('Mark Attendance', $labels);
        $this->assertStringNotContainsString('Create Project', $labels);
    }

    public function test_header_renders_more_actions_control(): void
    {
        if (! Route::has('crm.home')) {
            $this->markTestSkipped('crm.home is not registered.');
        }

        ['user' => $user, 'session' => $session] = $this->tenantWithRole('organization-owner');

        $this->actingAs($user)
            ->withSession($session)
            ->get(route('crm.home'))
            ->assertOk()
            ->assertSee('data-testid="header-quick-actions"', false)
            ->assertSee('More Actions', false);
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

    public function test_hrms_home_includes_projects_workspace_link(): void
    {
        if (! Route::has('hrms.home') || ! Route::has('projects.home')) {
            $this->markTestSkipped('Workspace homes are not registered.');
        }

        ['user' => $user, 'session' => $session] = $this->tenantWithRole('organization-owner');

        $projectsHref = route('projects.home');

        $this->actingAs($user)
            ->withSession($session)
            ->get(route('hrms.home'))
            ->assertOk()
            ->assertSee('data-workspace-id="projects"', false)
            ->assertSee($projectsHref, false);
    }

    public function test_switch_from_hrms_to_projects_persists_workspace(): void
    {
        if (! Route::has('hrms.home') || ! Route::has('projects.home')) {
            $this->markTestSkipped('Workspace homes are not registered.');
        }

        ['user' => $user, 'session' => $session] = $this->tenantWithRole('organization-owner');

        $this->actingAs($user)
            ->withSession($session)
            ->post(route('shell.workspace.switch'), ['workspace' => 'projects'])
            ->assertOk()
            ->assertJsonPath('workspace', 'projects')
            ->assertJsonPath('href', route('projects.home'));

        $prefs = UserUiPreference::query()->where('user_id', $user->id)->first();
        $this->assertSame('projects', $prefs?->last_workspace);
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
            ->assertSee('data-testid="header-workspace-switcher"', false)
            ->assertSee('x-show="open"', false)
            ->assertSee('x-cloak', false)
            ->assertSee('x-data="{', false)
            ->assertSee('href="'.route('projects.home').'"', false)
            ->assertSee('workspace-switcher-search', false)
            ->assertSee('Search workspaces', false)
            ->assertSee('data-workspace-id="crm"', false)
            ->assertSee('Current workspace', false);
    }

    public function test_application_shell_uses_fixed_chrome_classes(): void
    {
        if (! Route::has('crm.home')) {
            $this->markTestSkipped('crm.home is not registered.');
        }

        ['user' => $user, 'session' => $session] = $this->tenantWithRole('organization-owner');

        $this->actingAs($user)
            ->withSession($session)
            ->get(route('crm.home'))
            ->assertOk()
            ->assertSee('nova-shell', false)
            ->assertSee('nova-shell-sidebar', false)
            ->assertSee('nova-shell-main', false)
            ->assertSee('data-sidebar-collapsed', false)
            ->assertSee('nova-shell-content', false)
            ->assertSee('nova-header', false)
            ->assertSee('sticky top-0', false)
            ->assertSee('z-50', false)
            ->assertSee('CRM Workspace', false);
    }

    public function test_crm_menu_exposes_core_workspace_items(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->tenantWithRole('organization-owner');

        $labels = collect(app(NavigationService::class)->menuForWorkspace('crm', $user, $organization))
            ->pluck('label')
            ->all();

        foreach (['Dashboard', 'Leads', 'Customers', 'Pipeline', 'Activities', 'Products', 'Invoices', 'Payments', 'Reports'] as $expected) {
            $this->assertContains($expected, $labels, "CRM menu missing {$expected}");
        }
    }

    public function test_navigation_service_returns_role_scoped_workspaces(): void
    {
        ['user' => $owner, 'organization' => $organization] = $this->tenantWithRole('organization-owner');
        $ownerWorkspaces = app(NavigationService::class)
            ->availableWorkspaces($owner, $organization)
            ->pluck('id');

        $this->assertTrue($ownerWorkspaces->contains('crm'));
        $this->assertTrue($ownerWorkspaces->isNotEmpty());

        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');
        $employeeWorkspaces = app(NavigationService::class)
            ->availableWorkspaces($employee, $organization)
            ->pluck('id');

        $this->assertTrue($employeeWorkspaces->contains('hr') || $employeeWorkspaces->contains('home'));
        $this->assertFalse($employeeWorkspaces->contains('administration'));
    }

    public function test_sidebar_is_scoped_to_current_workspace(): void
    {
        if (! Route::has('crm.home') || ! Route::has('hrms.home')) {
            $this->markTestSkipped('Workspace homes are not registered.');
        }

        ['user' => $user, 'session' => $session] = $this->tenantWithRole('organization-owner');

        $this->actingAs($user)
            ->withSession($session)
            ->get(route('crm.home'))
            ->assertOk()
            ->assertSee('data-workspace="crm"', false)
            ->assertSee('data-workspace-nav="crm"', false);
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
