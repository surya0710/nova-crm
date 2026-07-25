<?php

namespace App\Services\Navigation;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserUiPreference;
use App\Services\Theme\ThemeService;

class NavigationContextManager
{
    public function __construct(
        protected WorkspaceResolver $workspaces,
        protected MenuBuilder $menus,
        protected BreadcrumbBuilder $breadcrumbs,
        protected FavoritePagesService $favorites,
        protected RecentPagesService $recents,
        protected PinnedPagesService $pinned,
        protected ThemeService $theme,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forRequest(User $user, ?Organization $organization): array
    {
        $prefs = $organization
            ? $this->theme->preferencesFor($user, $organization)
            : null;

        $preferredWorkspace = $prefs?->last_workspace;
        $currentWorkspace = $this->workspaces->resolveCurrent($preferredWorkspace, $user, $organization);

        $available = $this->workspaces->availableWorkspaces($user, $organization)
            ->map(function (array $ws) use ($currentWorkspace) {
                $ws['active'] = $ws['id'] === $currentWorkspace;

                return $ws;
            });

        $menu = $organization
            ? $this->menus->buildForWorkspace($currentWorkspace, $user, $organization)
            : collect();

        $currentMeta = $available->firstWhere('id', $currentWorkspace);

        return [
            'workspaces' => $available,
            'currentWorkspace' => $currentWorkspace,
            'currentWorkspaceMeta' => $currentMeta,
            'menu' => $menu,
            'favorites' => $organization ? $this->favorites->list($user, $organization) : collect(),
            'recents' => $organization ? $this->recents->list($user, $organization) : collect(),
            'pinned' => $organization ? $this->pinned->list($user, $organization) : collect(),
            'preferences' => $prefs,
            'sidebarCollapsed' => (bool) ($prefs?->sidebar_collapsed ?? false),
            'theme' => $prefs?->theme ?? 'light',
            'density' => $prefs?->density ?? 'comfortable',
            'branding' => $this->theme->brandingVariables($organization),
        ];
    }

    public function rememberWorkspace(User $user, Organization $organization, string $workspace): UserUiPreference
    {
        return $this->theme->updatePreferences($user, $organization, [
            'last_workspace' => $workspace,
        ]);
    }
}
