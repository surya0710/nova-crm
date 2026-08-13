<?php

namespace App\Services\Navigation;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class ShellQuickActionService
{
    /**
     * Resolve workspace-scoped quick actions split into primary + overflow.
     *
     * @return array{
     *     primary: array<int, array{label: string, href: string, variant?: string, priority?: int}>,
     *     overflow: array<int, array{label: string, href: string, variant?: string, priority?: int}>,
     *     all: array<int, array{label: string, href: string, variant?: string, priority?: int}>
     * }
     */
    public function forWorkspace(User $user, Organization $organization, string $workspaceId): array
    {
        $maxPrimary = (int) config('navigation.quick_action_limits.primary', 5);
        $definitions = config('navigation.quick_actions.'.$workspaceId, []);

        $actions = collect($definitions)
            ->filter(fn (array $action) => $this->isAllowed($action, $user, $organization))
            ->map(function (array $action) {
                $translated = __($action['label']);

                return [
                    'label' => is_string($translated) ? $translated : $action['label'],
                    'href' => route($action['route']),
                    'variant' => $action['variant'] ?? 'secondary',
                    'priority' => (int) ($action['priority'] ?? 100),
                    'group' => $action['group'] ?? (($action['variant'] ?? null) === 'primary' ? 'primary' : 'secondary'),
                ];
            })
            ->sortBy([
                fn (array $a) => ($a['group'] === 'primary' ? 0 : 1),
                fn (array $a) => $a['priority'],
                fn (array $a) => $a['label'],
            ])
            ->values();

        $primary = $actions->take(max(1, $maxPrimary))->values()->all();
        $overflow = $actions->slice(max(1, $maxPrimary))->values()->all();

        // Strip internal keys from payload used by Blade.
        $normalize = fn (array $rows) => array_map(function (array $row) {
            return [
                'label' => $row['label'],
                'href' => $row['href'],
                'variant' => $row['variant'],
            ];
        }, $rows);

        return [
            'primary' => $normalize($primary),
            'overflow' => $normalize($overflow),
            'all' => $normalize($actions->all()),
        ];
    }

    /**
     * Flat list for callers that only need links (workspace home bars, etc.).
     *
     * @return array<int, array{label: string, href: string, variant?: string}>
     */
    public function allForWorkspace(User $user, Organization $organization, string $workspaceId): array
    {
        return $this->forWorkspace($user, $organization, $workspaceId)['all'];
    }

    /**
     * @param  array<string, mixed>  $action
     */
    protected function isAllowed(array $action, User $user, Organization $organization): bool
    {
        if (! empty($action['permission']) && ! $user->hasPermission($action['permission'], $organization)) {
            return false;
        }

        if (! empty($action['any_permissions']) && ! $user->hasAnyPermission($action['any_permissions'], $organization)) {
            return false;
        }

        $routeName = $action['route'] ?? null;

        return $routeName && Route::has($routeName);
    }
}
