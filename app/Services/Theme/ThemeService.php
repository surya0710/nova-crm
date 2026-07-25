<?php

namespace App\Services\Theme;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserUiPreference;

class ThemeService
{
    public function preferencesFor(User $user, Organization $organization): UserUiPreference
    {
        return UserUiPreference::query()->firstOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id' => $user->id,
            ],
            [
                'theme' => 'light',
                'density' => 'comfortable',
                'sidebar_collapsed' => false,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updatePreferences(User $user, Organization $organization, array $attributes): UserUiPreference
    {
        $prefs = $this->preferencesFor($user, $organization);
        $prefs->fill(collect($attributes)->only([
            'theme',
            'density',
            'sidebar_collapsed',
            'last_workspace',
            'landing_page',
            'favorites',
            'pinned_pages',
            'recent_pages',
            'recent_searches',
            'recent_commands',
            'dashboard_layout',
            'meta',
        ])->all());
        $prefs->save();

        return $prefs;
    }

    /**
     * @return array<string, string>
     */
    public function brandingVariables(?Organization $organization): array
    {
        $vars = [];

        if (! $organization) {
            return $vars;
        }

        $settings = is_array($organization->settings ?? null) ? $organization->settings : [];
        $brand = $settings['branding'] ?? [];

        if (! empty($brand['primary_color']) && is_string($brand['primary_color'])) {
            $vars['--nova-brand-primary'] = $brand['primary_color'];
            $vars['--nova-color-primary-600'] = $brand['primary_color'];
        }

        if (! empty($brand['accent_color']) && is_string($brand['accent_color'])) {
            $vars['--nova-brand-accent'] = $brand['accent_color'];
            $vars['--nova-color-accent-600'] = $brand['accent_color'];
        }

        return $vars;
    }

    /**
     * Resolve effective theme for html[data-theme].
     */
    public function resolveTheme(?UserUiPreference $prefs): string
    {
        $theme = $prefs?->theme ?? 'light';

        if ($theme === 'system') {
            return 'light';
        }

        return in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
    }
}
