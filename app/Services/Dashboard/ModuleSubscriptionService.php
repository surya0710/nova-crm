<?php

namespace App\Services\Dashboard;

use App\Models\Organization;
use App\Models\OrganizationModule;
use App\Services\Modules\ModuleRegistry;
use Illuminate\Support\Facades\Schema;

class ModuleSubscriptionService
{
    public function __construct(
        protected ModuleRegistry $registry,
    ) {}

    public function moduleAllowed(Organization $organization, ?string $module): bool
    {
        if ($module === null) {
            return true;
        }

        $definition = $this->registry->get($module);
        if ($definition && ($definition['licensable'] ?? true) === false) {
            return true;
        }

        if ($module === 'common') {
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

        return $this->registry->planAllows($plan, $module);
    }

    /**
     * Explicitly enabled module keys, or null when unrestricted within the plan
     * (legacy organizations that have not been upgraded yet).
     *
     * @return list<string>|null
     */
    public function enabledModules(Organization $organization): ?array
    {
        if ($this->hasModuleAssignments($organization)) {
            return OrganizationModule::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->where('is_enabled', true)
                ->get()
                ->filter(fn (OrganizationModule $row) => $row->isEffectivelyEnabled())
                ->pluck('module_key')
                ->map(fn ($key) => (string) $key)
                ->values()
                ->all();
        }

        $settings = $organization->settings ?? [];

        if (! isset($settings['enabled_modules'])) {
            return null;
        }

        return array_values(array_filter((array) $settings['enabled_modules']));
    }

    /**
     * @return list<string>
     */
    public function availableModules(Organization $organization): array
    {
        $plan = $organization->plan ?? 'starter';
        $planModules = $this->registry->planModuleKeys($plan);
        $enabled = $this->enabledModules($organization);

        if ($enabled === null) {
            return $planModules;
        }

        return array_values(array_intersect($planModules, $enabled));
    }

    public function hasModuleAssignments(Organization $organization): bool
    {
        if (! Schema::hasTable('organization_modules')) {
            return false;
        }

        return OrganizationModule::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->exists();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function moduleCatalogForOrganization(Organization $organization): array
    {
        $enabled = $this->enabledModules($organization);
        $assignments = $this->assignmentsByKey($organization);

        return collect($this->registry->all())
            ->map(function (array $module) use ($organization, $enabled, $assignments) {
                $key = $module['key'];
                $planAllows = $this->planAllowsModule($organization, $key);
                $assignment = $assignments[$key] ?? null;
                $isEnabled = $enabled === null
                    ? $planAllows && ($module['enabled_by_default'] ?? true)
                    : in_array($key, $enabled, true);

                return [
                    'key' => $key,
                    'name' => $module['name'],
                    'description' => $module['description'] ?? '',
                    'icon' => $module['icon'] ?? 'cog',
                    'workspace' => $module['workspace'] ?? null,
                    'route' => $module['route'] ?? null,
                    'order' => $module['order'] ?? 100,
                    'licensable' => (bool) ($module['licensable'] ?? true),
                    'plan_allows' => $planAllows,
                    'enabled' => $isEnabled && ($planAllows || ! ($module['licensable'] ?? true)),
                    'included_in_subscription' => $assignment
                        ? (bool) $assignment->included_in_subscription
                        : $planAllows,
                    'is_trial' => $assignment ? (bool) $assignment->is_trial : false,
                    'is_addon' => $assignment ? (bool) $assignment->is_addon : false,
                    'source' => $assignment?->source ?? ($planAllows ? 'subscription' : 'manual'),
                    'expires_at' => $assignment?->expires_at?->toIso8601String(),
                ];
            })
            ->sortBy('order')
            ->values()
            ->all();
    }

    /**
     * @return array<string, OrganizationModule>
     */
    protected function assignmentsByKey(Organization $organization): array
    {
        if (! Schema::hasTable('organization_modules')) {
            return [];
        }

        return OrganizationModule::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->get()
            ->keyBy('module_key')
            ->all();
    }
}
