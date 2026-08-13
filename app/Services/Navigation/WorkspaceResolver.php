<?php

namespace App\Services\Navigation;

use App\Models\Organization;
use App\Models\User;
use App\Services\Dashboard\ModuleSubscriptionService;
use App\Services\Modules\ModuleRegistry;
use Illuminate\Support\Collection;

class WorkspaceResolver
{
    public function __construct(
        protected MenuBuilder $menuBuilder,
        protected ModuleRegistry $registry,
        protected ModuleSubscriptionService $modules,
    ) {}

    /**
     * @return Collection<int, array{id: string, label: string, icon: string, order: int, active: bool, href: string|null, footer?: bool, module?: string|null}>
     */
    public function availableWorkspaces(User $user, ?Organization $organization): Collection
    {
        if (! $organization) {
            return collect();
        }

        $visibility = $this->workspaceVisibility($organization);

        $workspaces = collect(config('navigation.workspaces', []))
            ->map(function (array $workspace) use ($user, $organization, $visibility) {
                $workspaceId = (string) ($workspace['id'] ?? '');
                if ($workspaceId === '') {
                    return null;
                }

                if (isset($visibility[$workspaceId]) && $visibility[$workspaceId] === false) {
                    return null;
                }

                if (! $this->workspaceLicensed($organization, $workspaceId)) {
                    return null;
                }

                if (isset($workspace['any_permissions']) && ! $this->menuBuilder->userHasAny($user, $organization, $workspace['any_permissions'])) {
                    return null;
                }

                $items = $this->menuBuilder->buildForWorkspace($workspaceId, $user, $organization);
                $hasItems = $items->isNotEmpty();

                if (($workspace['hide_when_empty'] ?? false) && ! $hasItems) {
                    return null;
                }

                $href = $this->resolveHref($workspace, $workspaceId, $items);
                if (! $href) {
                    // Authenticated users with module access must still see the workspace
                    // when a configured landing route exists, even if menu items are sparse.
                    return null;
                }

                $moduleKey = $workspace['module']
                    ?? $this->registry->moduleForWorkspace($workspaceId);

                return [
                    'id' => $workspaceId,
                    'label' => is_string($translated = __($workspace['label'])) ? $translated : $workspace['label'],
                    'icon' => $workspace['icon'] ?? 'home',
                    'order' => $workspace['order'] ?? 100,
                    'footer' => (bool) ($workspace['footer'] ?? false),
                    'href' => $href,
                    'module' => $moduleKey,
                    'search_scope' => config('navigation.workspace_search_scopes.'.$workspaceId, 'all'),
                    'active' => false,
                ];
            })
            ->filter()
            ->sortBy('order')
            ->values();

        // Never return an empty list for a member with an organization context when Home is reachable.
        if ($workspaces->isEmpty()) {
            $home = config('navigation.workspaces.home');
            if (is_array($home) && $this->menuBuilder->routeExists($home['route'] ?? 'dashboard')) {
                $homeLabel = $home['label'] ?? 'Home';
                $homeTranslated = __($homeLabel);

                return collect([[
                    'id' => 'home',
                    'label' => is_string($homeTranslated) ? $homeTranslated : $homeLabel,
                    'icon' => $home['icon'] ?? 'home',
                    'order' => $home['order'] ?? 10,
                    'footer' => false,
                    'href' => route($home['route'] ?? 'dashboard'),
                    'module' => null,
                    'search_scope' => config('navigation.workspace_search_scopes.home', 'all'),
                    'active' => false,
                ]]);
            }
        }

        return $workspaces;
    }

    public function resolveCurrent(?string $preferred, User $user, ?Organization $organization): string
    {
        $available = $this->availableWorkspaces($user, $organization)->pluck('id');

        // Route wins so the sidebar always mirrors the page the user is on.
        $fromRoute = $this->workspaceFromRoute();
        if ($fromRoute && $available->contains($fromRoute)) {
            return $fromRoute;
        }

        if ($preferred && $available->contains($preferred)) {
            return $preferred;
        }

        $default = $this->registry->defaultWorkspace();
        if ($available->contains($default)) {
            return $default;
        }

        return $available->first() ?? 'home';
    }

    public function workspaceFromRoute(?string $routeName = null): ?string
    {
        $routeName ??= request()->route()?->getName();
        if (! $routeName) {
            return null;
        }

        foreach (config('navigation.route_workspace_map', []) as $pattern => $workspace) {
            if ($this->routeMatches($routeName, $pattern)) {
                return $workspace;
            }
        }

        return null;
    }

    /**
     * Landing URL for a remembered workspace, with CRM / home fallbacks.
     */
    public function landingUrlFor(User $user, Organization $organization, ?string $preferredWorkspace = null): string
    {
        $workspaceId = $this->resolveCurrent($preferredWorkspace, $user, $organization);
        $available = $this->availableWorkspaces($user, $organization);
        $meta = $available->firstWhere('id', $workspaceId);

        if (! empty($meta['href'])) {
            return $meta['href'];
        }

        $crm = $available->firstWhere('id', 'crm');
        if (! empty($crm['href'])) {
            return $crm['href'];
        }

        $home = $available->firstWhere('id', 'home');
        if (! empty($home['href'])) {
            return $home['href'];
        }

        return route('dashboard');
    }

    /**
     * @param  Collection<int, array>  $items
     */
    protected function resolveHref(array $workspace, string $workspaceId, Collection $items): ?string
    {
        $routeName = $workspace['route']
            ?? $this->registry->routeForWorkspace($workspaceId);

        if (! empty($routeName) && $this->menuBuilder->routeExists($routeName)) {
            try {
                return route($routeName);
            } catch (\Throwable) {
                // fall through to first menu href
            }
        }

        return $this->firstHref($items);
    }

    protected function workspaceLicensed(Organization $organization, string $workspaceId): bool
    {
        if (! $this->registry->workspaceRequiresLicense($workspaceId)) {
            return true;
        }

        $moduleKey = $this->registry->moduleForWorkspace($workspaceId);
        if ($moduleKey === null) {
            return true;
        }

        return $this->modules->moduleAllowed($organization, $moduleKey);
    }

    /**
     * @return array<string, bool>
     */
    protected function workspaceVisibility(Organization $organization): array
    {
        $settings = is_array($organization->settings) ? $organization->settings : [];
        $stored = is_array($settings['workspace_visibility'] ?? null)
            ? $settings['workspace_visibility']
            : [];

        return array_merge($this->registry->defaultWorkspaceVisibility(), $stored);
    }

    protected function routeMatches(string $routeName, string $pattern): bool
    {
        if ($pattern === $routeName) {
            return true;
        }

        if (str_ends_with($pattern, '*')) {
            $prefix = rtrim($pattern, '*');

            return str_starts_with($routeName, $prefix);
        }

        return false;
    }

    /**
     * @param  Collection<int, array>  $items
     */
    protected function firstHref(Collection $items): ?string
    {
        foreach ($items as $item) {
            if (! empty($item['href'])) {
                return $item['href'];
            }
            if (! empty($item['children'])) {
                foreach ($item['children'] as $child) {
                    if (! empty($child['href'])) {
                        return $child['href'];
                    }
                }
            }
        }

        return null;
    }
}
