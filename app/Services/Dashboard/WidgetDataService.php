<?php

namespace App\Services\Dashboard;

use App\Models\DashboardWidget;
use App\Models\Organization;
use App\Models\User;

class WidgetDataService
{
    public function __construct(
        protected DashboardWidgetService $widgetService,
        protected DashboardCache $cache,
    ) {}

    /** @return array<string, mixed> */
    public function load(User $user, Organization $organization, DashboardWidget $widget, array $configuration = []): array
    {
        $provider = $this->widgetService->resolveProvider($widget);

        if (! $provider || ! $provider->authorize($user, $organization)) {
            return ['error' => 'unauthorized'];
        }

        return $this->cache->rememberWidget(
            $widget->widget_key,
            $organization->id,
            $user->id,
            fn () => $provider->load($user, $organization, $configuration)
        );
    }

    /** @param list<int> $widgetIds */
    public function loadMany(User $user, Organization $organization, array $widgetIds): array
    {
        $widgets = DashboardWidget::query()->whereIn('id', $widgetIds)->get()->keyBy('id');
        $data = [];

        foreach ($widgetIds as $widgetId) {
            $widget = $widgets->get($widgetId);
            if ($widget) {
                $data[$widget->widget_key] = $this->load($user, $organization, $widget);
            }
        }

        return $data;
    }

    /** @return array<string, mixed> */
    public function refresh(User $user, Organization $organization, DashboardWidget $widget): array
    {
        $this->cache->bump($organization->id);

        return $this->load($user, $organization, $widget);
    }

    /** @return array<string, mixed> */
    public function lazyLoad(User $user, Organization $organization, string $widgetKey): array
    {
        $widget = DashboardWidget::query()
            ->where('widget_key', $widgetKey)
            ->where(function ($q) use ($organization) {
                $q->whereNull('organization_id')
                    ->orWhere('organization_id', $organization->id);
            })
            ->firstOrFail();

        return $this->load($user, $organization, $widget);
    }
}
