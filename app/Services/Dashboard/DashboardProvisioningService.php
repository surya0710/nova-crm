<?php

namespace App\Services\Dashboard;

use App\Events\DashboardCreated;
use App\Models\DashboardQuickAction;
use App\Models\DashboardWidget;
use App\Models\Organization;
use App\Models\OrganizationDashboardWidget;
use App\Models\OrganizationQuickAction;
use Illuminate\Support\Facades\Schema;

class DashboardProvisioningService
{
    public function __construct(
        protected DashboardWidgetService $widgetService,
        protected QuickActionService $quickActionService,
        protected ModuleSubscriptionService $subscriptionService,
    ) {}

    public function provision(Organization $organization): void
    {
        if (! Schema::hasTable('dashboard_widgets') || ! Schema::hasTable('dashboard_quick_actions')) {
            return;
        }

        $this->widgetService->seedSystemWidgets();
        $this->quickActionService->seedSystemActions();

        $this->installDefaults($organization);

        event(new DashboardCreated($organization->id, [
            'widgets_provisioned' => OrganizationDashboardWidget::query()
                ->where('organization_id', $organization->id)
                ->count(),
            'quick_actions_provisioned' => OrganizationQuickAction::query()
                ->where('organization_id', $organization->id)
                ->count(),
        ]));
    }

    public function provisionForAllOrganizations(): void
    {
        if (! Schema::hasTable('dashboard_widgets') || ! Schema::hasTable('organization_dashboard_widgets')) {
            return;
        }

        Organization::query()->each(fn (Organization $org) => $this->installDefaults($org));
    }

    protected function installDefaults(Organization $organization): void
    {
        if (! Schema::hasTable('dashboard_widgets') || ! Schema::hasTable('organization_dashboard_widgets')) {
            return;
        }

        $availableModules = $this->subscriptionService->availableModules($organization);

        DashboardWidget::query()
            ->whereNull('organization_id')
            ->where('is_active', true)
            ->orderBy('default_position')
            ->each(function (DashboardWidget $widget, int $index) use ($organization, $availableModules) {
                $enabled = $widget->subscription_module === null
                    || $widget->subscription_module === 'common'
                    || in_array($widget->subscription_module, $availableModules, true);

                OrganizationDashboardWidget::query()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'widget_id' => $widget->id,
                    ],
                    [
                        'is_enabled' => $enabled,
                        'sort_order' => $index,
                    ]
                );
            });

        DashboardQuickAction::query()
            ->whereNull('organization_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->each(function (DashboardQuickAction $action, int $index) use ($organization, $availableModules) {
                $enabled = $action->subscription_module === null
                    || in_array($action->subscription_module, $availableModules, true);

                OrganizationQuickAction::query()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'quick_action_id' => $action->id,
                    ],
                    [
                        'is_enabled' => $enabled,
                        'sort_order' => $index,
                    ]
                );
            });
    }
}
