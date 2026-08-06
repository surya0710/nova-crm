<?php

namespace App\Services\Dashboard;

use App\Events\DashboardReset;
use App\Events\WidgetMoved;
use App\Events\WidgetResized;
use App\Models\DashboardWidget;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserDashboardPreference;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class DashboardPreferenceService
{
    public function __construct(
        protected DashboardCache $cache,
        protected AuditLogger $auditLogger,
    ) {}

    /** @param array<int, array<string, mixed>> $layout */
    public function saveLayout(User $user, Organization $organization, array $layout): void
    {
        DB::transaction(function () use ($user, $organization, $layout) {
            foreach ($layout as $item) {
                $widgetId = (int) ($item['widget_id'] ?? 0);
                if (! $widgetId) {
                    continue;
                }

                $pref = UserDashboardPreference::query()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'user_id' => $user->id,
                        'widget_id' => $widgetId,
                    ],
                    [
                        'position_x' => (int) ($item['position_x'] ?? 0),
                        'position_y' => (int) ($item['position_y'] ?? 0),
                        'width' => (int) ($item['width'] ?? 6),
                        'height' => (int) ($item['height'] ?? 4),
                        'is_visible' => (bool) ($item['is_visible'] ?? true),
                        'custom_configuration' => $item['custom_configuration'] ?? null,
                    ]
                );

                $this->auditLogger->log($pref, 'layout_updated', $item, $user);

                if (isset($item['position_x'], $item['position_y'])) {
                    event(new WidgetMoved(
                        $organization->id,
                        $user->id,
                        $widgetId,
                        (int) $item['position_x'],
                        (int) $item['position_y']
                    ));
                }

                if (isset($item['width'], $item['height'])) {
                    event(new WidgetResized(
                        $organization->id,
                        $user->id,
                        $widgetId,
                        (int) $item['width'],
                        (int) $item['height']
                    ));
                }
            }
        });

        $this->cache->clearUser($organization->id, $user->id);
    }

    public function resetLayout(User $user, Organization $organization): void
    {
        UserDashboardPreference::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->delete();

        event(new DashboardReset($organization->id, $user->id));
        $this->cache->clearUser($organization->id, $user->id);
    }

    /** @return \Illuminate\Support\Collection<int, UserDashboardPreference> */
    public function preferences(User $user, Organization $organization): \Illuminate\Support\Collection
    {
        return UserDashboardPreference::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->with('widget')
            ->get();
    }

    public function applyDefaultLayout(User $user, Organization $organization): void
    {
        $widgets = DashboardWidget::query()
            ->whereNull('organization_id')
            ->where('is_active', true)
            ->orderBy('default_position')
            ->get();

        $layout = $widgets->map(fn (DashboardWidget $widget, int $index) => [
            'widget_id' => $widget->id,
            'position_x' => 0,
            'position_y' => $index,
            'width' => $widget->default_width,
            'height' => $widget->default_height,
            'is_visible' => true,
        ])->all();

        $this->saveLayout($user, $organization, $layout);
    }

    public function hideWidget(User $user, Organization $organization, int $widgetId): void
    {
        UserDashboardPreference::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'widget_id' => $widgetId,
            ],
            ['is_visible' => false]
        );

        $this->cache->clearUser($organization->id, $user->id);
    }

    public function restoreWidget(User $user, Organization $organization, int $widgetId): void
    {
        UserDashboardPreference::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'widget_id' => $widgetId,
            ],
            ['is_visible' => true]
        );

        $this->cache->clearUser($organization->id, $user->id);
    }
}
