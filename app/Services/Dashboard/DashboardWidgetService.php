<?php

namespace App\Services\Dashboard;

use App\Contracts\DashboardWidgetDataProviderInterface;
use App\Events\WidgetDisabled;
use App\Events\WidgetEnabled;
use App\Events\WidgetRegistered;
use App\Models\DashboardSection;
use App\Models\DashboardWidget;
use App\Models\Organization;
use App\Models\OrganizationDashboardWidget;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DashboardWidgetService
{
    public function __construct(
        protected ModuleSubscriptionService $subscriptionService,
        protected AuditLogger $auditLogger,
    ) {}

    /** @return Collection<int, DashboardWidget> */
    public function discover(?Organization $organization = null): Collection
    {
        return DashboardWidget::query()
            ->where(function ($query) use ($organization) {
                $query->whereNull('organization_id');
                if ($organization) {
                    $query->orWhere('organization_id', $organization->id);
                }
            })
            ->where('is_active', true)
            ->with('section')
            ->orderBy('default_position')
            ->get();
    }

    public function register(array $definition, ?DashboardSection $section = null): DashboardWidget
    {
        $section ??= DashboardSection::query()
            ->whereNull('organization_id')
            ->where('slug', $definition['section'] ?? 'overview')
            ->firstOrFail();

        $widget = DashboardWidget::query()->updateOrCreate(
            [
                'organization_id' => null,
                'widget_key' => $definition['widget_key'],
            ],
            [
                'section_id' => $section->id,
                'module' => $definition['module'],
                'name' => $definition['name'],
                'description' => $definition['description'] ?? null,
                'icon' => $definition['icon'] ?? null,
                'permission_slug' => $definition['permission_slug'] ?? null,
                'subscription_module' => $definition['subscription_module'] ?? null,
                'default_width' => $definition['default_width'] ?? 6,
                'default_height' => $definition['default_height'] ?? 4,
                'default_position' => $definition['default_position'] ?? 0,
                'data_provider' => $definition['data_provider'],
                'configuration' => $definition['configuration'] ?? null,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        event(new WidgetRegistered($widget->id, $widget->widget_key));

        return $widget;
    }

    public function enable(Organization $organization, DashboardWidget $widget, ?User $actor = null): OrganizationDashboardWidget
    {
        $record = OrganizationDashboardWidget::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'widget_id' => $widget->id,
            ],
            [
                'is_enabled' => true,
                'sort_order' => $widget->default_position,
            ]
        );

        event(new WidgetEnabled($organization->id, $widget->id, $actor?->id));
        $this->auditLogger->log($record, 'widget_enabled', ['widget_key' => $widget->widget_key], $actor);

        return $record;
    }

    public function disable(Organization $organization, DashboardWidget $widget, ?User $actor = null): OrganizationDashboardWidget
    {
        $record = OrganizationDashboardWidget::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'widget_id' => $widget->id,
            ],
            ['is_enabled' => false]
        );

        event(new WidgetDisabled($organization->id, $widget->id, $actor?->id));
        $this->auditLogger->log($record, 'widget_disabled', ['widget_key' => $widget->widget_key], $actor);

        return $record;
    }

    public function validateWidget(DashboardWidget $widget, User $user, Organization $organization): bool
    {
        if (! $widget->is_active) {
            return false;
        }

        if (! $this->subscriptionService->moduleAllowed($organization, $widget->subscription_module)) {
            return false;
        }

        $orgWidget = OrganizationDashboardWidget::query()
            ->where('organization_id', $organization->id)
            ->where('widget_id', $widget->id)
            ->first();

        if ($orgWidget && ! $orgWidget->is_enabled) {
            return false;
        }

        if ($widget->permission_slug && ! $user->hasPermission($widget->permission_slug, $organization)) {
            return false;
        }

        return true;
    }

    public function resolveProvider(DashboardWidget $widget): ?DashboardWidgetDataProviderInterface
    {
        if (! class_exists($widget->data_provider)) {
            return null;
        }

        $provider = app($widget->data_provider);

        return $provider instanceof DashboardWidgetDataProviderInterface ? $provider : null;
    }

    public function seedSystemWidgets(): void
    {
        if (! Schema::hasTable('dashboard_sections') || ! Schema::hasTable('dashboard_widgets')) {
            return;
        }

        DB::transaction(function () {
            foreach (config('dashboard.sections', []) as $sectionDef) {
                DashboardSection::query()->updateOrCreate(
                    ['organization_id' => null, 'slug' => $sectionDef['slug']],
                    [
                        'name' => $sectionDef['name'],
                        'description' => $sectionDef['description'] ?? null,
                        'sort_order' => $sectionDef['sort_order'] ?? 0,
                        'is_system' => true,
                        'is_active' => true,
                    ]
                );
            }

            foreach (config('dashboard.widgets', []) as $key => $def) {
                $section = DashboardSection::query()
                    ->whereNull('organization_id')
                    ->where('slug', $def['section'])
                    ->first();

                if (! $section) {
                    throw ValidationException::withMessages([
                        'section' => "Dashboard section [{$def['section']}] not found for widget [{$key}].",
                    ]);
                }

                $this->register(array_merge($def, ['widget_key' => $key]), $section);
            }
        });
    }
}
