<?php

namespace App\Services\Configuration;

use App\Models\Organization;
use App\Models\User;
use App\Services\Theme\ThemeService;

/**
 * Recently used Configuration Hub sections, stored on existing user UI preferences.
 */
class ConfigurationRecentSettingsService
{
    public const META_KEY = 'recent_settings';

    public const MAX = 8;

    public function __construct(protected ThemeService $theme) {}

    /**
     * @param  array{key: string, label: string, href: string, module_key?: string, module_name?: string}  $section
     */
    public function record(User $user, Organization $organization, array $section): void
    {
        if (empty($section['key']) || empty($section['href']) || empty($section['label'])) {
            return;
        }

        $prefs = $this->theme->preferencesFor($user, $organization);
        $meta = is_array($prefs->meta) ? $prefs->meta : [];
        $recents = collect($meta[self::META_KEY] ?? [])
            ->reject(fn ($item) => ($item['key'] ?? null) === $section['key'])
            ->prepend([
                'key' => $section['key'],
                'label' => $section['label'],
                'href' => $section['href'],
                'module_key' => $section['module_key'] ?? null,
                'module_name' => $section['module_name'] ?? null,
                'visited_at' => now()->toIso8601String(),
            ])
            ->take(self::MAX)
            ->values()
            ->all();

        $meta[self::META_KEY] = $recents;

        $this->theme->updatePreferences($user, $organization, ['meta' => $meta]);
    }

    /**
     * Visible recently used settings only — never returns unlicensed or unauthorized sections.
     *
     * @param  list<array<string, mixed>>  $visibleSections
     * @return list<array<string, mixed>>
     */
    public function visible(User $user, Organization $organization, array $visibleSections): array
    {
        $allowed = collect($visibleSections)->keyBy('key');
        $prefs = $this->theme->preferencesFor($user, $organization);
        $meta = is_array($prefs->meta) ? $prefs->meta : [];

        return collect($meta[self::META_KEY] ?? [])
            ->map(function ($item) use ($allowed) {
                $key = $item['key'] ?? null;
                if (! is_string($key) || ! $allowed->has($key)) {
                    return null;
                }

                $section = $allowed->get($key);

                return [
                    'key' => $section['key'],
                    'label' => $section['label'],
                    'description' => $section['description'] ?? '',
                    'href' => $section['href'],
                    'module_key' => $section['module_key'] ?? null,
                    'module_name' => $section['module_name'] ?? null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
