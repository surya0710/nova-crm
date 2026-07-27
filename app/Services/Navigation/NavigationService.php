<?php

namespace App\Services\Navigation;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserUiPreference;
use App\Services\Theme\ThemeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Single source of truth for workspace-aware navigation.
 *
 * Workspace discovery and current-workspace resolution delegate to
 * WorkspaceResolver; menu/quick-action/search scope helpers live here so
 * consumers do not reach into config or resolver internals directly.
 */
class NavigationService
{
    public function __construct(
        protected WorkspaceResolver $workspaces,
        protected MenuBuilder $menus,
        protected ThemeService $theme,
    ) {}

    /**
     * Every workspace the current user can access for the organization
     * (licensed + RBAC + reachable landing href).
     *
     * @return Collection<int, array{id: string, label: string, icon: string, order: int, active: bool, href: string|null, footer?: bool, module?: string|null}>
     */
    public function availableWorkspaces(User $user, ?Organization $organization): Collection
    {
        return $this->workspaces->availableWorkspaces($user, $organization);
    }

    /**
     * Resolve the active workspace for the current request.
     *
     * Preference order: route map → preferred/last_workspace → org default → first available.
     */
    public function currentWorkspace(User $user, ?Organization $organization, ?string $preferred = null): string
    {
        return $this->workspaces->resolveCurrent($preferred, $user, $organization);
    }

    /**
     * @return Collection<int, array>
     */
    public function menuForWorkspace(string $workspaceId, User $user, ?Organization $organization): Collection
    {
        if (! $organization) {
            return collect();
        }

        return $this->menus->buildForWorkspace($workspaceId, $user, $organization);
    }

    public function resolveLandingUrl(User $user, Organization $organization, ?UserUiPreference $prefs = null): string
    {
        $prefs ??= $this->theme->preferencesFor($user, $organization);

        if ($prefs->landing_page && $this->routeAccessible($prefs->landing_page, $user, $organization)) {
            return route($prefs->landing_page);
        }

        $settings = is_array($organization->settings) ? $organization->settings : [];
        $orgLandingPages = array_merge(
            config('modules.default_landing_pages', []),
            is_array($settings['default_landing_pages'] ?? null) ? $settings['default_landing_pages'] : []
        );

        $persona = $this->resolvePersona($user, $organization);
        $routeName = $orgLandingPages[$persona]
            ?? $orgLandingPages['default']
            ?? config('navigation.persona_landing_pages.'.$persona)
            ?? config('navigation.persona_landing_pages.default', 'dashboard');

        if ($this->routeAccessible($routeName, $user, $organization)) {
            return route($routeName);
        }

        if (! empty($settings['default_workspace'])) {
            return $this->workspaces->landingUrlFor($user, $organization, $settings['default_workspace']);
        }

        if ($prefs->last_workspace) {
            return $this->workspaces->landingUrlFor($user, $organization, $prefs->last_workspace);
        }

        return $this->workspaces->landingUrlFor($user, $organization, null);
    }

    public function resolvePersona(User $user, Organization $organization): string
    {
        if ($user->isOwnerOf($organization)) {
            return 'owner';
        }

        if ($user->hasPermission('administration.view', $organization) || $user->hasPermission('users.manage', $organization)) {
            return 'admin';
        }

        if ($user->hasPermission('manager.dashboard', $organization)) {
            return 'manager';
        }

        if ($user->hasPermission('hr.dashboard', $organization) || $user->hasPermission('hrms.manage', $organization)) {
            return 'hr';
        }

        $roleSlug = $user->getRoleInOrganization($organization)?->slug;
        if ($roleSlug === 'employee' && $user->hasPermission('ess.access', $organization)) {
            return 'employee';
        }

        if ($user->hasPermission('ess.access', $organization) && ! $user->hasPermission('leads.view', $organization)) {
            return 'employee';
        }

        if ($user->hasPermission('projects.manage', $organization)) {
            return 'project_manager';
        }

        if ($user->hasPermission('leads.view', $organization) || $user->hasPermission('opportunities.view', $organization)) {
            return 'sales';
        }

        return 'default';
    }

    /**
     * @return array<string, string>
     */
    public function workspaceSearchScopes(): array
    {
        return config('navigation.workspace_search_scopes', []);
    }

    public function defaultSearchScopeForWorkspace(string $workspaceId): string
    {
        return config('navigation.workspace_search_scopes.'.$workspaceId, 'all');
    }

    protected function routeAccessible(string $routeName, User $user, Organization $organization): bool
    {
        if (! Route::has($routeName)) {
            return false;
        }

        $route = Route::getRoutes()->getByName($routeName);
        if (! $route) {
            return false;
        }

        $middleware = collect($route->gatherMiddleware());
        foreach ($middleware as $mw) {
            if (is_string($mw) && str_starts_with($mw, 'permission:')) {
                $permission = substr($mw, strlen('permission:'));
                if (! $user->hasPermission($permission, $organization)) {
                    return false;
                }
            }
        }

        return true;
    }
}
