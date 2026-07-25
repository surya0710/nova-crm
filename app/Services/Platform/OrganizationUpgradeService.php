<?php

namespace App\Services\Platform;

use App\Models\Organization;
use App\Models\OrganizationModule;
use App\Models\PlatformUser;
use App\Models\User;
use App\Services\Dashboard\DashboardProvisioningService;
use App\Services\Dashboard\ModuleSubscriptionService;
use App\Services\Modules\ModuleRegistry;
use App\Services\NotificationPreferenceService;
use App\Services\Theme\ThemeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrganizationUpgradeService
{
    public function __construct(
        protected ModuleRegistry $registry,
        protected ModuleSubscriptionService $modules,
        protected DashboardProvisioningService $dashboardProvisioning,
        protected ThemeService $theme,
        protected NotificationPreferenceService $notificationPreferences,
    ) {}

    /**
     * Idempotent upgrade for a single organization. Never overwrites existing data.
     *
     * @return array{
     *     organization_id: int,
     *     organization_name: string,
     *     modules_added: list<string>,
     *     modules_checked: list<string>,
     *     settings_updated: list<string>,
     *     dashboard_preferences: bool,
     *     users_upgraded: int,
     *     notification_preferences: int,
     *     workspace_preferences: bool,
     *     dry_run: bool
     * }
     */
    public function upgrade(Organization $organization, bool $dryRun = false): array
    {
        $result = [
            'organization_id' => $organization->id,
            'organization_name' => $organization->name,
            'modules_added' => [],
            'modules_checked' => [],
            'settings_updated' => [],
            'dashboard_preferences' => false,
            'users_upgraded' => 0,
            'notification_preferences' => 0,
            'workspace_preferences' => false,
            'dry_run' => $dryRun,
        ];

        if ($dryRun) {
            return $this->preview($organization, $result);
        }

        return DB::transaction(function () use ($organization, $result) {
            $result['modules_added'] = $this->ensureModuleAssignments($organization);
            $result['modules_checked'] = $this->registry->keys();
            $result['settings_updated'] = $this->ensureOrganizationSettings($organization);
            $result['workspace_preferences'] = in_array('workspace_visibility', $result['settings_updated'], true)
                || in_array('default_landing_pages', $result['settings_updated'], true)
                || in_array('default_workspace', $result['settings_updated'], true);

            if (Schema::hasTable('dashboard_widgets')) {
                $this->dashboardProvisioning->provision($organization);
                $result['dashboard_preferences'] = true;
            }

            $userStats = $this->ensureUserPreferences($organization);
            $result['users_upgraded'] = $userStats['users'];
            $result['notification_preferences'] = $userStats['notifications'];

            $organization->refresh();

            return $result;
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function upgradeAll(bool $dryRun = false): array
    {
        $results = [];

        Organization::query()
            ->orderBy('id')
            ->each(function (Organization $organization) use (&$results, $dryRun) {
                $results[] = $this->upgrade($organization, $dryRun);
            });

        return $results;
    }

    /**
     * @param  list<string>  $enabledKeys
     */
    public function syncModuleAssignments(
        Organization $organization,
        array $enabledKeys,
        ?PlatformUser $actor = null,
        string $source = 'manual',
    ): void {
        if (! Schema::hasTable('organization_modules')) {
            return;
        }

        $enabledKeys = array_values(array_unique(array_filter($enabledKeys)));
        $plan = $organization->plan ?? 'starter';

        foreach ($this->registry->all() as $key => $definition) {
            $licensable = (bool) ($definition['licensable'] ?? true);
            $shouldEnable = ! $licensable || in_array($key, $enabledKeys, true);
            $planAllows = $this->registry->planAllows($plan, $key);

            OrganizationModule::withoutGlobalScopes()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'module_key' => $key,
                ],
                [
                    'is_enabled' => $shouldEnable,
                    'source' => $licensable ? $source : 'subscription',
                    'included_in_subscription' => $planAllows,
                    'is_trial' => false,
                    'is_addon' => $source === 'addon',
                ]
            );
        }

        $this->syncSettingsEnabledModules($organization, $enabledKeys);
    }

    /**
     * Create missing module assignment rows from plan defaults + legacy settings.
     *
     * @return list<string>
     */
    protected function ensureModuleAssignments(Organization $organization): array
    {
        if (! Schema::hasTable('organization_modules')) {
            return [];
        }

        $added = [];
        $plan = $organization->plan ?? 'starter';
        $legacyEnabled = $this->legacyEnabledModules($organization);
        $existing = OrganizationModule::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->pluck('module_key')
            ->all();

        foreach ($this->registry->all() as $key => $definition) {
            if (in_array($key, $existing, true)) {
                continue;
            }

            $planAllows = $this->registry->planAllows($plan, $key);
            $licensable = (bool) ($definition['licensable'] ?? true);

            if ($legacyEnabled !== null) {
                $isEnabled = ! $licensable || in_array($key, $legacyEnabled, true);
            } else {
                $isEnabled = ! $licensable || ($planAllows && ($definition['enabled_by_default'] ?? true));
            }

            OrganizationModule::withoutGlobalScopes()->create([
                'organization_id' => $organization->id,
                'module_key' => $key,
                'is_enabled' => $isEnabled,
                'source' => $planAllows ? 'subscription' : 'manual',
                'included_in_subscription' => $planAllows,
                'is_trial' => false,
                'is_addon' => false,
            ]);

            $added[] = $key;
        }

        if ($added !== []) {
            $enabled = OrganizationModule::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->where('is_enabled', true)
                ->pluck('module_key')
                ->all();
            $this->syncSettingsEnabledModules($organization, $enabled);
        }

        return $added;
    }

    /**
     * @return list<string>
     */
    protected function ensureOrganizationSettings(Organization $organization): array
    {
        $settings = is_array($organization->settings) ? $organization->settings : [];
        $updated = [];

        if (! isset($settings['feature_toggles']) || ! is_array($settings['feature_toggles'])) {
            $settings['feature_toggles'] = $this->registry->defaultFeatureToggles();
            $updated[] = 'feature_toggles';
        } else {
            $merged = array_merge($this->registry->defaultFeatureToggles(), $settings['feature_toggles']);
            if ($merged !== $settings['feature_toggles']) {
                $settings['feature_toggles'] = $merged;
                $updated[] = 'feature_toggles';
            }
        }

        if (! isset($settings['workspace_visibility']) || ! is_array($settings['workspace_visibility'])) {
            $settings['workspace_visibility'] = $this->registry->defaultWorkspaceVisibility();
            $updated[] = 'workspace_visibility';
        } else {
            $merged = array_merge($this->registry->defaultWorkspaceVisibility(), $settings['workspace_visibility']);
            if ($merged !== $settings['workspace_visibility']) {
                $settings['workspace_visibility'] = $merged;
                $updated[] = 'workspace_visibility';
            }
        }

        if (! isset($settings['default_landing_pages']) || ! is_array($settings['default_landing_pages'])) {
            $settings['default_landing_pages'] = $this->registry->defaultLandingPages();
            $updated[] = 'default_landing_pages';
        } else {
            $merged = array_merge($this->registry->defaultLandingPages(), $settings['default_landing_pages']);
            if ($merged !== $settings['default_landing_pages']) {
                $settings['default_landing_pages'] = $merged;
                $updated[] = 'default_landing_pages';
            }
        }

        if (! isset($settings['default_workspace']) || ! is_string($settings['default_workspace'])) {
            $settings['default_workspace'] = $this->registry->defaultWorkspace();
            $updated[] = 'default_workspace';
        }

        if (! isset($settings['enabled_modules']) && $this->modules->hasModuleAssignments($organization)) {
            $settings['enabled_modules'] = OrganizationModule::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->where('is_enabled', true)
                ->pluck('module_key')
                ->values()
                ->all();
            $updated[] = 'enabled_modules';
        }

        if ($updated !== []) {
            $organization->update(['settings' => $settings]);
        }

        return $updated;
    }

    /**
     * @return array{users: int, notifications: int}
     */
    protected function ensureUserPreferences(Organization $organization): array
    {
        $usersUpgraded = 0;
        $notifications = 0;

        $organization->users()->each(function (User $user) use ($organization, &$usersUpgraded, &$notifications) {
            $createdUi = false;

            if (Schema::hasTable('user_ui_preferences')) {
                $prefs = $this->theme->preferencesFor($user, $organization);
                if ($prefs->wasRecentlyCreated) {
                    $createdUi = true;
                }

                // Ensure search preference storage exists without overwriting history.
                if ($prefs->recent_searches === null) {
                    $prefs->recent_searches = [];
                    $prefs->save();
                    $createdUi = true;
                }
            }

            if (Schema::hasTable('notification_preferences')) {
                $preference = $this->notificationPreferences->getOrCreate($user, $organization->id);
                if ($preference->wasRecentlyCreated) {
                    $notifications++;
                    $createdUi = true;
                }
            }

            if ($createdUi) {
                $usersUpgraded++;
            }
        });

        return [
            'users' => $usersUpgraded,
            'notifications' => $notifications,
        ];
    }

    /**
     * @param  list<string>  $enabledKeys
     */
    protected function syncSettingsEnabledModules(Organization $organization, array $enabledKeys): void
    {
        $settings = is_array($organization->settings) ? $organization->settings : [];
        $settings['enabled_modules'] = array_values(array_unique($enabledKeys));
        $organization->update(['settings' => $settings]);
    }

    /**
     * @return list<string>|null
     */
    protected function legacyEnabledModules(Organization $organization): ?array
    {
        $settings = $organization->settings ?? [];

        if (! isset($settings['enabled_modules'])) {
            return null;
        }

        return array_values(array_filter((array) $settings['enabled_modules']));
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    protected function preview(Organization $organization, array $result): array
    {
        $existing = Schema::hasTable('organization_modules')
            ? OrganizationModule::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->pluck('module_key')
                ->all()
            : [];

        foreach ($this->registry->keys() as $key) {
            $result['modules_checked'][] = $key;
            if (! in_array($key, $existing, true)) {
                $result['modules_added'][] = $key;
            }
        }

        $settings = is_array($organization->settings) ? $organization->settings : [];
        foreach (['feature_toggles', 'workspace_visibility', 'default_landing_pages', 'default_workspace'] as $key) {
            if (! isset($settings[$key])) {
                $result['settings_updated'][] = $key;
            }
        }

        $result['workspace_preferences'] = in_array('workspace_visibility', $result['settings_updated'], true);
        $result['dashboard_preferences'] = Schema::hasTable('dashboard_widgets');
        $result['users_upgraded'] = $organization->users()->count();

        return $result;
    }
}
