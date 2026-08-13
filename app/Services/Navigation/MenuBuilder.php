<?php

namespace App\Services\Navigation;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class MenuBuilder
{
    /**
     * @return Collection<int, array>
     */
    public function buildForWorkspace(string $workspaceId, User $user, ?Organization $organization): Collection
    {
        $items = config("navigation.menus.{$workspaceId}", []);

        return collect($items)
            ->map(fn (array $item) => $this->normalizeItem($item, $user, $organization))
            ->filter()
            ->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function normalizeItem(array $item, User $user, ?Organization $organization): ?array
    {
        if (! empty($item['permission']) && ! $this->userCan($user, $organization, $item['permission'])) {
            return null;
        }

        if (! empty($item['any_permissions']) && ! $this->userHasAny($user, $organization, $item['any_permissions'])) {
            return null;
        }

        $children = collect($item['children'] ?? [])
            ->map(fn (array $child) => $this->normalizeItem($child, $user, $organization))
            ->filter()
            ->values()
            ->all();

        $href = null;
        $active = false;

        if (! empty($item['route'])) {
            if (! $this->routeExists($item['route'])) {
                if ($children === []) {
                    return null;
                }
            } else {
                try {
                    $href = route($item['route']);
                } catch (\Throwable) {
                    if ($children === []) {
                        return null;
                    }
                }
            }

            $match = $item['match'] ?? [$item['route']];
            $active = $this->isActive($match);
        }

        if ($children !== []) {
            $active = $active || collect($children)->contains(fn ($c) => $c['active'] ?? false);
        }

        if (! $href && $children === []) {
            return null;
        }

        $label = $item['label'] ?? '';
        if (! empty($item['term']) && function_exists('crm_term')) {
            $label = crm_term($item['term']);
        } else {
            $translated = __($label);
            // On case-insensitive filesystems, __("Attendance") can resolve to lang/en/attendance.php (array).
            $label = is_string($translated) ? $translated : $label;
        }

        return [
            'label' => $label,
            'href' => $href,
            'icon' => $item['icon'] ?? null,
            'active' => $active,
            'badge' => $item['badge'] ?? null,
            'tier' => $item['tier'] ?? 'core',
            'children' => $children,
            'open' => $active,
        ];
    }

    public function userCan(User $user, ?Organization $organization, string $permission): bool
    {
        return $user->hasPermission($permission, $organization);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function userHasAny(User $user, ?Organization $organization, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->userCan($user, $organization, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function routeExists(string $name): bool
    {
        return Route::has($name);
    }

    /**
     * @param  array<int, string>|string  $patterns
     */
    public function isActive(array|string $patterns): bool
    {
        $patterns = (array) $patterns;

        foreach ($patterns as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }
}
