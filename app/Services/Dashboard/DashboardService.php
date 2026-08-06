<?php

namespace App\Services\Dashboard;

use App\Models\DashboardWidget;
use App\Models\Organization;
use App\Models\OrganizationDashboardWidget;
use App\Models\User;
use App\Models\UserDashboardPreference;

class DashboardService
{
    public function __construct(
        protected DashboardWidgetService $widgetService,
        protected ModuleSubscriptionService $subscriptionService,
        protected DashboardCache $cache,
    ) {}

    /** @return array<string, mixed> */
    public function build(User $user, Organization $organization, bool $includeData = false): array
    {
        return $this->cache->remember(
            'dashboard.build.'.($includeData ? 'with_data' : 'layout'),
            $organization->id,
            $user->id,
            fn () => $this->buildUncached($user, $organization, $includeData)
        );
    }

    /** @return array<string, mixed> */
    protected function buildUncached(User $user, Organization $organization, bool $includeData): array
    {
        $widgets = $this->loadWidgets($user, $organization);
        $sections = $widgets
            ->groupBy(fn ($w) => $w['section']['slug'] ?? 'overview')
            ->map(fn ($items, $slug) => [
                'slug' => $slug,
                'name' => $items->first()['section']['name'] ?? ucfirst($slug),
                'widgets' => $items->values()->all(),
            ])
            ->values()
            ->all();

        $payload = [
            'organization_id' => $organization->id,
            'sections' => $sections,
            'widgets' => $widgets->values()->all(),
        ];

        if ($includeData) {
            $payload['widget_data'] = app(WidgetDataService::class)
                ->loadMany($user, $organization, $widgets->pluck('id')->all());
        }

        return $payload;
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    public function loadWidgets(User $user, Organization $organization): \Illuminate\Support\Collection
    {
        $systemWidgets = $this->widgetService->discover($organization);

        $orgConfigs = OrganizationDashboardWidget::query()
            ->where('organization_id', $organization->id)
            ->get()
            ->keyBy('widget_id');

        $preferences = UserDashboardPreference::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('widget_id');

        return $systemWidgets
            ->filter(fn (DashboardWidget $widget) => $this->widgetService->validateWidget($widget, $user, $organization))
            ->map(function (DashboardWidget $widget) use ($orgConfigs, $preferences) {
                $pref = $preferences->get($widget->id);
                $orgConfig = $orgConfigs->get($widget->id);

                return [
                    'id' => $widget->id,
                    'widget_key' => $widget->widget_key,
                    'name' => $widget->name,
                    'description' => $widget->description,
                    'icon' => $widget->icon,
                    'module' => $widget->module,
                    'section' => [
                        'slug' => $widget->section?->slug,
                        'name' => $widget->section?->name,
                    ],
                    'layout' => [
                        'position_x' => $pref?->position_x ?? 0,
                        'position_y' => $pref?->position_y ?? $widget->default_position,
                        'width' => $pref?->width ?? $widget->default_width,
                        'height' => $pref?->height ?? $widget->default_height,
                        'is_visible' => $pref?->is_visible ?? true,
                    ],
                    'configuration' => array_merge(
                        $widget->configuration ?? [],
                        $orgConfig?->configuration ?? [],
                        $pref?->custom_configuration ?? []
                    ),
                ];
            })
            ->filter(fn (array $w) => $w['layout']['is_visible'])
            ->values();
    }

    public function applyOrganizationConfiguration(Organization $organization, array $widgetConfigs): void
    {
        foreach ($widgetConfigs as $widgetId => $config) {
            OrganizationDashboardWidget::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'widget_id' => $widgetId,
                ],
                [
                    'is_enabled' => $config['is_enabled'] ?? true,
                    'sort_order' => $config['sort_order'] ?? 0,
                    'configuration' => $config['configuration'] ?? null,
                ]
            );
        }

        $this->cache->bump($organization->id);
    }
}
