<?php

namespace App\Services\Administration;

use App\Models\Organization;
use App\Models\User;
use App\Services\Dashboard\ModuleSubscriptionService;
use App\Services\Modules\ModuleRegistry;

class OrganizationModulesService
{
    public function __construct(
        protected ModuleSubscriptionService $modules,
        protected ModuleRegistry $registry,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(Organization $organization): array
    {
        $catalog = $this->modules->moduleCatalogForOrganization($organization);
        $settings = is_array($organization->settings) ? $organization->settings : [];
        $featureToggles = is_array($settings['feature_toggles'] ?? null) ? $settings['feature_toggles'] : [];
        $workspaceVisibility = is_array($settings['workspace_visibility'] ?? null) ? $settings['workspace_visibility'] : [];
        $landingPages = is_array($settings['default_landing_pages'] ?? null) ? $settings['default_landing_pages'] : [];

        $moduleRows = collect($catalog)->map(function (array $module) {
            return [
                'key' => $module['key'],
                'label' => __($module['name']),
                'description' => __($module['description']),
                'icon' => $module['icon'],
                'plan_allows' => $module['plan_allows'],
                'enabled' => $module['enabled'],
                'included_in_subscription' => $module['included_in_subscription'],
                'is_trial' => $module['is_trial'],
                'is_addon' => $module['is_addon'],
                'expires_at' => $module['expires_at'],
            ];
        })->values()->all();

        return [
            'plan' => $organization->plan ?? 'starter',
            'modules' => $moduleRows,
            'feature_toggles' => array_merge($this->defaultFeatureToggles(), $featureToggles),
            'workspace_visibility' => array_merge($this->defaultWorkspaceVisibility(), $workspaceVisibility),
            'default_landing_pages' => array_merge($this->defaultLandingPages(), $landingPages),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Organization $organization, array $input, User $actor): void
    {
        $settings = $organization->settings ?? [];

        if (isset($input['feature_toggles']) && is_array($input['feature_toggles'])) {
            $toggles = [];
            foreach ($this->defaultFeatureToggles() as $key => $default) {
                $toggles[$key] = (bool) ($input['feature_toggles'][$key] ?? false);
            }
            $settings['feature_toggles'] = $toggles;
        }

        if (isset($input['workspace_visibility']) && is_array($input['workspace_visibility'])) {
            $visibility = [];
            foreach ($this->defaultWorkspaceVisibility() as $key => $default) {
                $visibility[$key] = (bool) ($input['workspace_visibility'][$key] ?? false);
            }
            $settings['workspace_visibility'] = $visibility;
        }

        if (isset($input['default_landing_pages']) && is_array($input['default_landing_pages'])) {
            $pages = [];
            foreach ($this->defaultLandingPages() as $role => $default) {
                $value = trim((string) ($input['default_landing_pages'][$role] ?? $default));
                $pages[$role] = $value !== '' ? $value : $default;
            }
            $settings['default_landing_pages'] = $pages;
        }

        $settings['modules_updated_by'] = $actor->id;
        $settings['modules_updated_at'] = now()->toIso8601String();

        $organization->update(['settings' => $settings]);
    }

    /**
     * @return array<string, bool>
     */
    public function defaultFeatureToggles(): array
    {
        return $this->registry->defaultFeatureToggles();
    }

    /**
     * @return array<string, bool>
     */
    public function defaultWorkspaceVisibility(): array
    {
        return $this->registry->defaultWorkspaceVisibility();
    }

    /**
     * @return array<string, string>
     */
    public function defaultLandingPages(): array
    {
        return $this->registry->defaultLandingPages();
    }
}
