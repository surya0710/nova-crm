<?php

namespace App\Services\Platform;

use App\Models\Organization;
use App\Models\PlatformUser;
use App\Services\Dashboard\ModuleSubscriptionService;
use Illuminate\Support\Arr;

class PlatformLicensingService
{
    public function __construct(
        protected PlatformSubscriptionService $subscriptions,
        protected ModuleSubscriptionService $modules,
        protected PlatformAuditService $audit,
        protected PlatformConfigurationService $configuration,
    ) {}

    public function index(): array
    {
        $moduleList = config('dashboard.modules');

        if (! is_array($moduleList) || $moduleList === []) {
            $moduleList = collect(config('dashboard.plan_modules', []))
                ->flatMap(fn ($modules) => $modules === '*' ? [] : (array) $modules)
                ->unique()
                ->values()
                ->all();
        }

        return [
            'plans' => $this->subscriptions->planCatalog(),
            'modules' => $moduleList,
            'plan_modules' => config('dashboard.plan_modules', []),
            'organizations' => \App\Models\Organization::query()
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
        $settings = $organization->settings ?? [];
        $settings['enabled_modules'] = array_values(array_unique($modules));
        $organization->update(['settings' => $settings]);

        $this->audit->log('organization.modules_assigned', $actor, $organization, [
            'modules' => $settings['enabled_modules'],
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
            'quotas' => $organization->settings['quotas'] ?? ($definition['limits'] ?? []),
            'usage' => [
                'users' => $organization->users()->count(),
                'storage_mb' => round(($organization->storage_used_bytes ?? 0) / 1048576, 2),
            ],
        ];
    }
}
