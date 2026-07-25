<?php

namespace App\Services\Platform;

use App\Models\Organization;
use App\Models\PlatformUser;
use App\Services\Dashboard\DashboardProvisioningService;
use App\Services\Dashboard\ModuleSubscriptionService;
use App\Services\Modules\ModuleRegistry;
use Illuminate\Support\Arr;

class PlatformLicensingService
{
    public function __construct(
        protected PlatformSubscriptionService $subscriptions,
        protected ModuleSubscriptionService $modules,
        protected PlatformAuditService $audit,
        protected PlatformConfigurationService $configuration,
        protected ModuleRegistry $registry,
        protected OrganizationUpgradeService $upgrade,
        protected DashboardProvisioningService $dashboardProvisioning,
    ) {}

    public function index(): array
    {
        $moduleList = collect($this->registry->all())
            ->mapWithKeys(fn (array $module, string $key) => [$key => $module['name']])
            ->all();

        return [
            'plans' => $this->subscriptions->planCatalog(),
            'modules' => $moduleList,
            'plan_modules' => config('modules.plan_modules', []),
            'organizations' => Organization::query()
                ->orderBy('name')
                ->limit(100)
                ->get(['id', 'name', 'plan']),
        ];
    }

    public function updatePlanDefinition(string $slug, array $data, PlatformUser $actor): array
    {
        $overrides = $this->configuration->get('licensing', 'plan_overrides', []);
        $overrides[$slug] = array_replace_recursive($overrides[$slug] ?? [], Arr::only($data, [
            'name', 'description', 'modules', 'limits', 'features',
        ]));

        $this->configuration->set('licensing', 'plan_overrides', $overrides, $actor);

        $this->audit->log('licensing.plan_updated', $actor, null, [
            'plan' => $slug,
            'overrides' => $overrides[$slug],
        ]);

        return $this->subscriptions->planCatalog()[$slug] ?? [];
    }

    public function assignModules(Organization $organization, array $modules, PlatformUser $actor): Organization
    {
        $allowed = array_values(array_filter(
            $modules,
            fn (string $key) => $this->registry->exists($key)
        ));

        $this->upgrade->syncModuleAssignments($organization, $allowed, $actor, 'manual');
        $this->dashboardProvisioning->provision($organization->fresh());

        $this->audit->log('organization.modules_assigned', $actor, $organization, [
            'modules' => $allowed,
        ]);

        return $organization->fresh();
    }

    public function setQuotas(Organization $organization, array $quotas, PlatformUser $actor): Organization
    {
        $settings = $organization->settings ?? [];
        $settings['quotas'] = array_merge($settings['quotas'] ?? [], Arr::only($quotas, [
            'users', 'storage_mb', 'api_requests_per_day',
        ]));
        $organization->update(['settings' => $settings]);

        $this->audit->log('organization.quotas_updated', $actor, $organization, [
            'quotas' => $settings['quotas'],
        ]);

        return $organization->fresh();
    }

    public function organizationLicensing(Organization $organization): array
    {
        $plan = $organization->plan ?? 'starter';
        $catalog = $this->subscriptions->planCatalog();
        $definition = $catalog[$plan] ?? [];

        return [
            'plan' => $plan,
            'definition' => $definition,
            'available_modules' => $this->modules->availableModules($organization),
            'enabled_modules' => $this->modules->enabledModules($organization),
            'module_catalog' => $this->modules->moduleCatalogForOrganization($organization),
            'quotas' => $organization->settings['quotas'] ?? ($definition['limits'] ?? []),
            'usage' => [
                'users' => $organization->users()->count(),
                'storage_mb' => round(($organization->storage_used_bytes ?? 0) / 1048576, 2),
            ],
        ];
    }
}
