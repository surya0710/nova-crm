<?php

namespace App\Services\Modules;

/**
 * Read-only access to config/modules.php — the single module registry.
 */
class ModuleRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return config('modules.modules', []);
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->all());
    }

    /**
     * Modules that platform admins can enable/disable per organization.
     *
     * @return array<string, array<string, mixed>>
     */
    public function licensable(): array
    {
        return array_filter(
            $this->all(),
            fn (array $module) => (bool) ($module['licensable'] ?? true)
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    public function exists(string $key): bool
    {
        return isset($this->all()[$key]);
    }

    /**
     * @return array<string, mixed>|list<string>
     */
    public function planModules(string $plan): array|string
    {
        return config("modules.plan_modules.{$plan}", config('modules.plan_modules.starter', []));
    }

    /**
     * @return list<string>
     */
    public function planModuleKeys(string $plan): array
    {
        $modules = $this->planModules($plan);

        if ($modules === '*') {
            return $this->keys();
        }

        return array_values((array) $modules);
    }

    public function planAllows(string $plan, string $module): bool
    {
        $definition = $this->get($module);
        if ($definition && ($definition['licensable'] ?? true) === false) {
            return true;
        }

        $modules = $this->planModules($plan);

        if ($modules === '*') {
            return $this->exists($module);
        }

        return in_array($module, (array) $modules, true);
    }

    public function moduleForWorkspace(string $workspaceId): ?string
    {
        $fromNavigation = config("navigation.workspaces.{$workspaceId}.module");
        if (is_string($fromNavigation) && $fromNavigation !== '') {
            return $fromNavigation;
        }

        // Explicit null in navigation means the workspace is not module-licensed.
        if (array_key_exists('module', (array) config("navigation.workspaces.{$workspaceId}", []))) {
            return null;
        }

        $map = config('modules.workspace_module_map', []);

        return $map[$workspaceId] ?? null;
    }

    public function workspaceForModule(string $moduleKey): ?string
    {
        return $this->get($moduleKey)['workspace'] ?? null;
    }

    /**
     * @return list<string>
     */
    public function alwaysAvailableWorkspaces(): array
    {
        return array_values(config('modules.always_available_workspaces', ['home', 'administration']));
    }

    public function workspaceRequiresLicense(string $workspaceId): bool
    {
        return ! in_array($workspaceId, $this->alwaysAvailableWorkspaces(), true)
            && $this->moduleForWorkspace($workspaceId) !== null;
    }

    public function defaultWorkspace(): string
    {
        return (string) config('modules.default_workspace', 'crm');
    }

    public function routeForModule(string $moduleKey): ?string
    {
        $route = $this->get($moduleKey)['route'] ?? null;

        return is_string($route) && $route !== '' ? $route : null;
    }

    public function routeForWorkspace(string $workspaceId): ?string
    {
        $moduleKey = $this->moduleForWorkspace($workspaceId);
        if ($moduleKey) {
            $route = $this->routeForModule($moduleKey);
            if ($route) {
                return $route;
            }
        }

        $workspace = config("navigation.workspaces.{$workspaceId}");

        return is_array($workspace) && ! empty($workspace['route'])
            ? (string) $workspace['route']
            : null;
    }

    /**
     * @return array<string, bool>
     */
    public function defaultFeatureToggles(): array
    {
        return config('modules.default_feature_toggles', []);
    }

    /**
     * @return array<string, bool>
     */
    public function defaultWorkspaceVisibility(): array
    {
        return config('modules.default_workspace_visibility', []);
    }

    /**
     * @return array<string, string>
     */
    public function defaultLandingPages(): array
    {
        return config('modules.default_landing_pages', []);
    }
}
