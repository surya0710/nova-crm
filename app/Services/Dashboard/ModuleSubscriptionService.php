<?php

namespace App\Services\Dashboard;

use App\Models\Organization;

class ModuleSubscriptionService
{
    public function moduleAllowed(Organization $organization, ?string $module): bool
    {
        if ($module === null || $module === 'common') {
            return true;
        }

        if (! $this->planAllowsModule($organization, $module)) {
            return false;
        }

        $enabled = $this->enabledModules($organization);

        if ($enabled === null) {
            return true;
        }

        return in_array($module, $enabled, true);
    }

    public function planAllowsModule(Organization $organization, string $module): bool
    {
        $plan = $organization->plan ?? 'starter';
        $planModules = config("dashboard.plan_modules.{$plan}", config('dashboard.plan_modules.starter', []));

        if ($planModules === '*') {
            return true;
        }

        return in_array($module, $planModules, true);
    }

    /** @return list<string>|null */
    public function enabledModules(Organization $organization): ?array
    {
        $settings = $organization->settings ?? [];

        if (! isset($settings['enabled_modules'])) {
            return null;
        }

        return array_values(array_filter((array) $settings['enabled_modules']));
    }

    /** @return list<string> */
    public function availableModules(Organization $organization): array
    {
        $plan = $organization->plan ?? 'starter';
        $planModules = config("dashboard.plan_modules.{$plan}", config('dashboard.plan_modules.starter', []));

        if ($planModules === '*') {
            return config('dashboard.modules', []);
        }

        $enabled = $this->enabledModules($organization);

        if ($enabled === null) {
            return $planModules;
        }

        return array_values(array_intersect($planModules, $enabled));
    }
}
