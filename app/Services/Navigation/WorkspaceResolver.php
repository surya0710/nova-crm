<?php

namespace App\Services\Navigation;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;

class WorkspaceResolver
{
    public function __construct(
        protected MenuBuilder $menuBuilder,
    ) {}

    /**
     * @return Collection<int, array{id: string, label: string, icon: string, order: int, active: bool, href: string|null}>
     */
    public function availableWorkspaces(User $user, ?Organization $organization): Collection
    {
        if (! $organization) {
            return collect();
        }

        return collect(config('navigation.workspaces', []))
            ->map(function (array $workspace) use ($user, $organization) {
                $items = $this->menuBuilder->buildForWorkspace($workspace['id'], $user, $organization);
                $hasItems = $items->isNotEmpty();

                if (($workspace['hide_when_empty'] ?? false) && ! $hasItems) {
                    return null;
                }

                if (isset($workspace['any_permissions']) && ! $this->menuBuilder->userHasAny($user, $organization, $workspace['any_permissions'])) {
                    return null;
                }

                if (! $hasItems && empty($workspace['route'])) {
                    return null;
                }

                $href = null;
                if (! empty($workspace['route']) && $this->menuBuilder->routeExists($workspace['route'])) {
                    $href = route($workspace['route']);
                } elseif ($items->isNotEmpty()) {
                    $href = $this->firstHref($items);
                }

                if (! $href) {
                    return null;
                }

                return [
                    'id' => $workspace['id'],
                    'label' => __($workspace['label']),
                    'icon' => $workspace['icon'] ?? 'home',
                    'order' => $workspace['order'] ?? 100,
                    'footer' => (bool) ($workspace['footer'] ?? false),
                    'href' => $href,
                    'active' => false,
                ];
            })
            ->filter()
            ->sortBy('order')
            ->values();
    }

    public function resolveCurrent(?string $preferred, User $user, ?Organization $organization): string
    {
        $available = $this->availableWorkspaces($user, $organization)->pluck('id');

        if ($preferred && $available->contains($preferred)) {
            return $preferred;
        }

        $fromRoute = $this->workspaceFromRoute();
        if ($fromRoute && $available->contains($fromRoute)) {
            return $fromRoute;
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
