<?php

namespace App\Services\Navigation;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class ShellQuickActionService
{
    /**
     * @return array<int, array{label: string, href: string, variant?: string}>
     */
    public function forWorkspace(User $user, Organization $organization, string $workspaceId): array
    {
        $definitions = config('navigation.quick_actions.'.$workspaceId, []);

        return collect($definitions)
            ->filter(function (array $action) use ($user, $organization) {
                if (! empty($action['permission']) && ! $user->hasPermission($action['permission'], $organization)) {
                    return false;
                }

                if (! empty($action['any_permissions']) && ! $user->hasAnyPermission($action['any_permissions'], $organization)) {
                    return false;
                }

                // permission => null means visible to any authenticated org member
                $routeName = $action['route'] ?? null;

                return $routeName && Route::has($routeName);
            })
            ->map(fn (array $action) => [
                'label' => __($action['label']),
                'href' => route($action['route']),
                'variant' => $action['variant'] ?? 'secondary',
            ])
            ->values()
            ->all();
    }
}
