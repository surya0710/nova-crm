<?php

namespace App\Services\Administration;

use App\Models\Organization;
use App\Models\User;
use App\Services\Dashboard\ModuleSubscriptionService;

class OrganizationModulesService
{
    public function __construct(
        protected ModuleSubscriptionService $modules,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(Organization $organization): array
    {
        $all = config('dashboard.modules', []);
        $available = $this->modules->availableModules($organization);
        $plan = $organization->plan ?? 'starter';
        $settings = is_array($organization->settings) ? $organization->settings : [];
        $featureToggles = is_array($settings['feature_toggles'] ?? null) ? $settings['feature_toggles'] : [];
        $workspaceVisibility = is_array($settings['workspace_visibility'] ?? null) ? $settings['workspace_visibility'] : [];
        $landingPages = is_array($settings['default_landing_pages'] ?? null) ? $settings['default_landing_pages'] : [];

        $moduleRows = collect($all)->map(function (string $module) use ($organization, $available) {
            $planAllows = $this->modules->planAllowsModule($organization, $module);
            $enabled = in_array($module, $available, true);

            return [
                'key' => $module,
                'label' => __(ucfirst(str_replace('_', ' ', $module))),
                'plan_allows' => $planAllows,
                'enabled' => $enabled && $planAllows,
            ];
        })->values()->all();

        return [
            'plan' => $plan,
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
        return [
            'command_palette' => true,
            'global_search' => true,
            'ai_assist' => false,
            'advanced_workflows' => true,
            'public_api' => true,
            'email_digests' => true,
        ];
    }

    /**
     * @return array<string, bool>
     */
    public function defaultWorkspaceVisibility(): array
    {
        return [
            'crm' => true,
            'projects' => true,
            'hr' => true,
            'marketing' => true,
            'operations' => true,
            'analytics' => true,
            'administration' => true,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function defaultLandingPages(): array
    {
        return [
            'default' => 'dashboard',
            'sales' => 'crm.home',
            'project_manager' => 'projects.home',
            'hr' => 'hrms.home',
            'admin' => 'administration.home',
        ];
    }
}
