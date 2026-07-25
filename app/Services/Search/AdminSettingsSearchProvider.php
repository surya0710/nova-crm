<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class AdminSettingsSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'settings';
    }

    public function label(): string
    {
        return __('Settings');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasAnyPermission(['settings.manage', 'organization.hr_config.manage', 'hrms.view', 'rbac.view', 'integrations.view', 'api.tokens'])) {
            return collect();
        }

        $query = trim(mb_strtolower($query));
        if ($query === '') {
            return collect();
        }

        $sections = config('organization_settings.sections', []);

        return collect($sections)
            ->filter(function (array $section) use ($user, $query) {
                $label = mb_strtolower((string) ($section['label'] ?? ''));
                if ($label === '' || ! str_contains($label, $query)) {
                    return false;
                }

                $permission = $section['permission'] ?? null;
                $fallback = $section['fallback_permission'] ?? null;

                if ($permission && ! $user->hasPermission($permission)) {
                    if (! $fallback || ! $user->hasPermission($fallback)) {
                        return false;
                    }
                }

                $routeName = $section['route'] ?? null;

                return $routeName && Route::has($routeName);
            })
            ->take($limit)
            ->map(function (array $section) {
                $routeName = $section['route'];
                $url = route($routeName, $section['query'] ?? []);

                return [
                    'type' => __('Setting'),
                    'label' => $this->label(),
                    'title' => __($section['label']),
                    'subtitle' => __($section['group'] ?? 'organization'),
                    'url' => $url,
                    'workspace' => 'administration',
                ];
            })
            ->values();
    }
}
