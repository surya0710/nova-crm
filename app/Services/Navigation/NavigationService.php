<?php

namespace App\Services\Navigation;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserUiPreference;
use App\Services\Theme\ThemeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Single source of truth for the Workspace Platform.
 *
 * All shell UI (sidebar, header switcher, search scope, quick actions,
 * breadcrumbs) must consume this service — not config or WorkspaceResolver
 * directly.
 */
class NavigationService
{
    public function __construct(
        protected WorkspaceResolver $workspaces,
        protected MenuBuilder $menus,
        protected ShellQuickActionService $quickActionService,
        protected FavoritePagesService $favorites,
        protected FavoriteWorkspacesService $favoriteWorkspaces,
        protected RecentPagesService $recents,
        protected PinnedPagesService $pinned,
        protected BreadcrumbBuilder $breadcrumbs,
        protected ThemeService $theme,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function availableWorkspaces(User $user, ?Organization $organization): Collection
    {
        return $this->workspaces->availableWorkspaces($user, $organization);
    }

    public function currentWorkspace(User $user, ?Organization $organization, ?string $preferred = null): string
    {
        return $this->workspaces->resolveCurrent($preferred, $user, $organization);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function menuForWorkspace(string $workspaceId, User $user, ?Organization $organization): Collection
    {
        if (! $organization) {
            return collect();
        }

        return $this->menus->buildForWorkspace($workspaceId, $user, $organization);
    }

    /**
     * Workspace-scoped quick actions (primary + overflow).
     *
     * @return array{
     *     primary: array<int, array{label: string, href: string, variant?: string}>,
     *     overflow: array<int, array{label: string, href: string, variant?: string}>,
     *     all: array<int, array{label: string, href: string, variant?: string}>
     * }
     */
    public function quickActions(User $user, Organization $organization, string $workspaceId): array
    {
        return $this->quickActionService->forWorkspace($user, $organization, $workspaceId);
    }

    public function searchScope(string $workspaceId): string
    {
        return $this->defaultSearchScopeForWorkspace($workspaceId);
    }

    /**
     * Full shell navigation payload for the authenticated tenant request.
     *
     * @return array<string, mixed>
     */
    public function forShell(User $user, ?Organization $organization): array
    {
        if (! $organization) {
            return $this->emptyShell();
        }

        $prefs = $this->theme->preferencesFor($user, $organization);
        $preferredWorkspace = $prefs->last_workspace;
        $currentWorkspace = $this->currentWorkspace($user, $organization, $preferredWorkspace);

        $available = $this->availableWorkspaces($user, $organization)
            ->map(function (array $ws) use ($currentWorkspace) {
                $ws['active'] = $ws['id'] === $currentWorkspace;

                return $ws;
            })
            ->values();

        $menu = $this->menuForWorkspace($currentWorkspace, $user, $organization)->values();
        $currentMeta = $available->firstWhere('id', $currentWorkspace);

        $favoriteWorkspaceItems = $this->favoriteWorkspaces->list($user, $organization)
            ->map(fn ($id) => $available->firstWhere('id', $id))
            ->filter()
            ->values();

        $recentWorkspaceItems = $this->favoriteWorkspaces->recent($user, $organization)
            ->map(fn ($id) => $available->firstWhere('id', $id))
            ->filter()
            ->values();

        return [
            // Plain arrays so Blade component props never drop Collections.
            'workspaces' => $available->all(),
            'currentWorkspace' => $currentWorkspace,
            'currentWorkspaceMeta' => $currentMeta,
            'menu' => $menu->all(),
            'favorites' => $this->favorites->list($user, $organization)->values()->all(),
            'favoriteWorkspaces' => $favoriteWorkspaceItems->all(),
            'recentWorkspaces' => $recentWorkspaceItems->all(),
            'recents' => $this->recents->list($user, $organization)->values()->all(),
            'pinned' => $this->pinned->list($user, $organization)->values()->all(),
            'quickActions' => $this->quickActions($user, $organization, $currentWorkspace),
            'searchDefaultScope' => $this->searchScope($currentWorkspace),
            'breadcrumbs' => $this->breadcrumbsFor($currentMeta)->all(),
            'preferences' => $prefs,
            'defaultWorkspace' => $prefs->default_workspace,
            'sidebarCollapsed' => (bool) ($prefs->sidebar_collapsed ?? false),
            'theme' => $prefs->theme ?? 'light',
            'density' => $prefs->density ?? 'comfortable',
            'branding' => $this->theme->brandingVariables($organization),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function emptyShell(): array
    {
        return [
            'workspaces' => [],
            'currentWorkspace' => 'home',
            'currentWorkspaceMeta' => null,
            'menu' => [],
            'favorites' => [],
            'favoriteWorkspaces' => [],
            'recentWorkspaces' => [],
            'recents' => [],
            'pinned' => [],
            'quickActions' => ['primary' => [], 'overflow' => [], 'all' => []],
            'searchDefaultScope' => 'all',
            'breadcrumbs' => [],
            'preferences' => null,
            'defaultWorkspace' => null,
            'sidebarCollapsed' => false,
            'theme' => 'light',
            'density' => 'comfortable',
            'branding' => [],
        ];
    }

    public function rememberWorkspace(User $user, Organization $organization, string $workspace): UserUiPreference
    {
        $this->favoriteWorkspaces->rememberRecent($user, $organization, $workspace);

        return $this->theme->updatePreferences($user, $organization, [
            'last_workspace' => $workspace,
        ]);
    }

    /**
     * Post-login / home landing URL.
     *
     * Priority: user preferred workspace → persona default → organization
     * default workspace → last workspace → system default.
     *
     * Organization default is applied after persona so role-based landings
     * (e.g. Employee → HRMS) are not skipped when orgs ship with a provisioned
     * `settings.default_workspace` (usually CRM).
     *
     * Optional user `landing_page` (route) is treated as an explicit user
     * preference and wins only when no default_workspace is set.
     */
    public function resolveLandingUrl(User $user, Organization $organization, ?UserUiPreference $prefs = null): string
    {
        $prefs ??= $this->theme->preferencesFor($user, $organization);
        $settings = is_array($organization->settings) ? $organization->settings : [];

        // 1. User preferred workspace
        if ($url = $this->urlForWorkspace($user, $organization, $prefs->default_workspace)) {
            return $url;
        }

        // 1b. Legacy user landing_page route (still a user preference)
        if ($prefs->landing_page && $this->routeAccessible($prefs->landing_page, $user, $organization)) {
            return route($prefs->landing_page);
        }

        // 2. Persona default workspace
        $persona = $this->resolvePersona($user, $organization);
        $personaWorkspace = config('navigation.persona_default_workspaces.'.$persona)
            ?? config('navigation.persona_default_workspaces.default');
        if ($url = $this->urlForWorkspace($user, $organization, $personaWorkspace)) {
            return $url;
        }

        // Employee ESS fallback when the HR workspace home is not available
        if ($persona === 'employee' && Route::has('ess.dashboard')) {
            $essRoute = config('navigation.persona_landing_pages.employee', 'ess.dashboard');
            if ($essRoute === 'ess.dashboard' || $this->routeAccessible($essRoute, $user, $organization)) {
                return route($essRoute);
            }
        }

        // 3. Organization default workspace
        if (! empty($settings['default_workspace']) && ($url = $this->urlForWorkspace($user, $organization, $settings['default_workspace']))) {
            return $url;
        }

        // 4. Last active workspace
        if ($url = $this->urlForWorkspace($user, $organization, $prefs->last_workspace)) {
            return $url;
        }

        // 5. System default workspace / first available
        $systemDefault = config('modules.default_workspace', 'crm');
        if ($url = $this->urlForWorkspace($user, $organization, $systemDefault)) {
            return $url;
        }

        return $this->workspaces->landingUrlFor($user, $organization, null);
    }

    /**
     * Resolve a workspace home URL when the workspace is available to the user.
     */
    public function urlForWorkspace(User $user, Organization $organization, ?string $workspaceId): ?string
    {
        if (! $workspaceId) {
            return null;
        }

        $meta = $this->availableWorkspaces($user, $organization)->firstWhere('id', $workspaceId);
        if (empty($meta['href'])) {
            return null;
        }

        return $meta['href'];
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
        if ($roleSlug === 'employee') {
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

    /**
     * @param  array<string, mixed>|null  $currentMeta
     * @return Collection<int, array{label: string, href: string|null, current: bool}>
     */
    protected function breadcrumbsFor(?array $currentMeta): Collection
    {
        if (! $currentMeta) {
            return collect();
        }

        return $this->breadcrumbs->build([
            [
                'label' => $currentMeta['label'] ?? __('Workspace'),
                'href' => $currentMeta['href'] ?? null,
            ],
        ]);
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
