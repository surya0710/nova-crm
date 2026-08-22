<?php

namespace App\Services\Configuration;

use App\Models\Organization;
use App\Models\User;
use App\Services\Dashboard\ModuleSubscriptionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Module-aware Configuration Hub catalog.
 *
 * Reads config/organization_settings.php and filters by plan, enabled modules,
 * and the current user's permissions. Does not own settings storage.
 */
class ConfigurationRegistry
{
    public function __construct(
        protected ModuleSubscriptionService $modules,
    ) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $modules = config('organization_settings.modules', []);
        if (is_array($modules) && $modules !== []) {
            return $modules;
        }

        return $this->modulesFromLegacySections();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * Presentation modules the user may see in the Configuration Hub.
     *
     * @return list<array<string, mixed>>
     */
    public function visibleModules(User $user, Organization $organization): array
    {
        return collect($this->all())
            ->sortBy(fn (array $module) => $module['order'] ?? 100)
            ->map(function (array $module) use ($user, $organization) {
                if (! $this->moduleLicenseAllowed($organization, $module['license'] ?? null)) {
                    return null;
                }

                if (! $this->userCanAccessModule($user, $organization, $module)) {
                    return null;
                }

                $sections = $this->visibleSections($user, $organization, $module);
                if ($sections === []) {
                    return null;
                }

                return [
                    'key' => $module['key'],
                    'name' => trans_string($module['name']),
                    'description' => trans_string($module['description'] ?? ''),
                    'icon' => $module['icon'] ?? 'cog',
                    'license' => $module['license'] ?? null,
                    'order' => $module['order'] ?? 100,
                    'sections' => $sections,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Flat section list for search and command palette.
     *
     * @return list<array<string, mixed>>
     */
    public function visibleSectionsForSearch(User $user, Organization $organization): array
    {
        return collect($this->visibleModules($user, $organization))
            ->flatMap(function (array $module) {
                return collect($module['sections'])->map(function (array $section) use ($module) {
                    $section['module_key'] = $module['key'];
                    $section['module_name'] = $module['name'];
                    $section['module_description'] = $module['description'] ?? '';

                    return $section;
                });
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $module
     * @return list<array<string, mixed>>
     */
    public function visibleSections(User $user, Organization $organization, array $module): array
    {
        $inheritedLicense = $module['license'] ?? null;

        return collect($module['sections'] ?? [])
            ->sortBy(fn (array $section) => $section['order'] ?? 100)
            ->map(function (array $section, string $key) use ($user, $organization, $inheritedLicense) {
                $license = $section['license'] ?? $inheritedLicense;
                if (! $this->moduleLicenseAllowed($organization, $license)) {
                    return null;
                }

                if (! $this->userCanAccessSection($user, $organization, $section)) {
                    return null;
                }

                $href = $this->href($section);
                if ($href === null) {
                    return null;
                }

                return [
                    'key' => $key,
                    'label' => trans_string($section['label']),
                    'description' => trans_string($section['description'] ?? ''),
                    'keywords' => $this->sectionKeywords($key, $section),
                    'route' => $section['route'] ?? null,
                    'href' => $href,
                    'permission' => $section['permission'] ?? null,
                    'license' => $license,
                    'order' => $section['order'] ?? 100,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $section
     */
    public function userCanAccessSection(User $user, Organization $organization, array $section): bool
    {
        if ($user->is_super_admin || $user->isOwnerOf($organization)) {
            return true;
        }

        $any = $section['any_permissions'] ?? null;
        if (is_array($any) && $any !== []) {
            return $user->hasAnyPermission($any, $organization);
        }

        $permission = $section['permission'] ?? null;
        $fallback = $section['fallback_permission'] ?? null;

        if ($permission && $user->hasPermission($permission, $organization)) {
            return true;
        }

        if ($fallback && $user->hasPermission($fallback, $organization)) {
            return true;
        }

        return $permission === null;
    }

    /**
     * @param  array<string, mixed>  $module
     */
    public function userCanAccessModule(User $user, Organization $organization, array $module): bool
    {
        if ($user->is_super_admin || $user->isOwnerOf($organization)) {
            return true;
        }

        $permission = $module['permission'] ?? null;
        if ($permission === null) {
            return true;
        }

        return $user->hasPermission($permission, $organization);
    }

    public function moduleLicenseAllowed(Organization $organization, ?string $license): bool
    {
        if ($license === null || $license === '') {
            return true;
        }

        return $this->modules->moduleAllowed($organization, $license);
    }

    /**
     * @param  array<string, mixed>  $section
     */
    public function href(array $section): ?string
    {
        $routeName = $section['route'] ?? null;
        if (! is_string($routeName) || $routeName === '' || ! Route::has($routeName)) {
            return null;
        }

        try {
            return route($routeName, $section['query'] ?? []);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * License that must be allowed to open this route from a catalogued section.
     *
     * Shared routes (for example organization.edit) that also serve unlicensed
     * sections return null so profile/email stay reachable.
     */
    public function requiredLicenseForRoute(string $routeName): ?string
    {
        $licenses = collect($this->rawSectionsForRoute($routeName))
            ->map(fn (array $section) => $section['license'] ?? null)
            ->unique()
            ->values();

        if ($licenses->isEmpty() || $licenses->contains(null) || $licenses->contains('')) {
            return null;
        }

        if ($licenses->count() === 1) {
            $license = $licenses->first();

            return is_string($license) && $license !== '' ? $license : null;
        }

        return null;
    }

    /**
     * First catalogued section for a route (used for recents and breadcrumbs).
     *
     * @return array<string, mixed>|null
     */
    public function sectionByRoute(string $routeName): ?array
    {
        $matches = $this->rawSectionsForRoute($routeName);

        return $matches[0] ?? null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rawSectionsForRoute(string $routeName): array
    {
        $matches = [];

        foreach ($this->all() as $module) {
            $inheritedLicense = $module['license'] ?? null;
            foreach ($module['sections'] ?? [] as $key => $section) {
                if (($section['route'] ?? null) !== $routeName) {
                    continue;
                }

                $matches[] = [
                    'key' => $key,
                    'label' => trans_string($section['label'] ?? $key),
                    'description' => trans_string($section['description'] ?? ''),
                    'route' => $routeName,
                    'query' => $section['query'] ?? [],
                    'license' => $section['license'] ?? $inheritedLicense,
                    'module_key' => $module['key'],
                    'module_name' => trans_string($module['name']),
                    'href' => $this->href($section),
                ];
            }
        }

        return $matches;
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return list<array<string, mixed>>
     */
    public function filterSectionsByQuery(array $sections, string $query): array
    {
        $query = trim(mb_strtolower($query));
        if ($query === '') {
            return $sections;
        }

        return collect($sections)
            ->filter(fn (array $section) => $this->sectionMatchesQuery($section, $query))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $section
     */
    public function sectionMatchesQuery(array $section, string $query): bool
    {
        $haystack = mb_strtolower(implode(' ', array_filter([
            $section['key'] ?? '',
            str_replace('_', ' ', (string) ($section['key'] ?? '')),
            $section['label'] ?? '',
            $section['description'] ?? '',
            $section['module_key'] ?? '',
            $section['module_name'] ?? '',
            $section['module_description'] ?? '',
            implode(' ', $section['keywords'] ?? []),
        ])));

        $tokens = preg_split('/\s+/', trim(mb_strtolower($query))) ?: [];
        if ($tokens === [] || $tokens === ['']) {
            return false;
        }

        foreach ($tokens as $token) {
            if ($token === '' || ! str_contains($haystack, $token)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<array{label: string, href?: string|null, current?: bool}>
     */
    public function breadcrumbItems(?string $routeName, ?string $currentLabel = null): array
    {
        $hubHref = Route::has('organization.settings.hub') ? route('organization.settings.hub') : null;
        $items = [
            [
                'label' => trans_string('Administration'),
                'href' => Route::has('administration.home') ? route('administration.home') : null,
            ],
        ];

        $isHub = $routeName === 'organization.settings.hub';
        $section = (! $isHub && $routeName) ? $this->sectionByRoute($routeName) : null;

        if ($isHub || ($section === null && ($currentLabel === null || $currentLabel === 'Configuration Hub'))) {
            $items[] = [
                'label' => trans_string('Configuration Hub'),
                'current' => true,
            ];

            return $items;
        }

        $items[] = [
            'label' => trans_string('Configuration Hub'),
            'href' => $hubHref,
        ];

        if ($section) {
            $items[] = [
                'label' => trans_string($section['module_name']),
                'href' => $hubHref ? $hubHref.'#module-'.$section['module_key'] : null,
            ];
            $label = $currentLabel ?: $section['label'];
            $items[] = [
                'label' => trans_string($label),
                'current' => true,
            ];

            return $items;
        }

        $items[] = [
            'label' => trans_string($currentLabel ?: 'Settings'),
            'current' => true,
        ];

        return $items;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return list<string>
     */
    protected function sectionKeywords(string $key, array $section): array
    {
        $keywords = array_merge(
            [$key, str_replace('_', ' ', $key), trans_string($section['label'] ?? '')],
            is_array($section['keywords'] ?? null) ? $section['keywords'] : [],
        );

        return array_values(array_unique(array_filter(array_map(
            static fn ($word) => is_string($word) ? trim($word) : '',
            $keywords,
        ))));
    }

    /**
     * @return Collection<string, array<string, mixed>>
     */
    public function futureModules(): Collection
    {
        return collect(config('organization_settings.future_modules', []));
    }

    /**
     * Rebuild the hub catalog from the pre-module-aware sections list.
     * Used when production still has a cached config without `modules`.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function modulesFromLegacySections(): array
    {
        $sections = config('organization_settings.sections', []);
        $groups = config('organization_settings.groups', []);
        if (! is_array($sections) || $sections === []) {
            return [];
        }

        $modules = [];

        foreach ($sections as $key => $section) {
            if (! is_array($section)) {
                continue;
            }

            $group = (string) ($section['group'] ?? $section['module'] ?? 'organization');
            if (! isset($modules[$group])) {
                $modules[$group] = [
                    'key' => $group,
                    'name' => is_string($groups[$group] ?? null)
                        ? $groups[$group]
                        : ucfirst(str_replace('_', ' ', $group)),
                    'description' => '',
                    'icon' => 'cog',
                    'license' => $section['license'] ?? null,
                    'permission' => null,
                    'order' => count($modules) * 10 + 10,
                    'sections' => [],
                ];
            }

            $modules[$group]['sections'][(string) $key] = $section;
        }

        return $modules;
    }
}
