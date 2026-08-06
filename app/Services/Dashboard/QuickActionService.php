<?php

namespace App\Services\Dashboard;

use App\Events\QuickActionCreated;
use App\Events\QuickActionUpdated;
use App\Models\DashboardQuickAction;
use App\Models\Organization;
use App\Models\OrganizationQuickAction;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class QuickActionService
{
    public function __construct(
        protected ModuleSubscriptionService $subscriptionService,
        protected AuditLogger $auditLogger,
    ) {}

    /** @return Collection<int, array<string, mixed>> */
    public function available(User $user, Organization $organization): Collection
    {
        $actions = DashboardQuickAction::query()
            ->where(function ($query) use ($organization) {
                $query->whereNull('organization_id')
                    ->orWhere('organization_id', $organization->id);
            })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $orgConfigs = OrganizationQuickAction::query()
            ->where('organization_id', $organization->id)
            ->get()
            ->keyBy('quick_action_id');

        return $actions
            ->filter(fn (DashboardQuickAction $action) => $this->validate($action, $user, $organization, $orgConfigs->get($action->id)))
            ->map(fn (DashboardQuickAction $action) => $this->format($action, $orgConfigs->get($action->id)))
            ->values();
    }

    public function validate(
        DashboardQuickAction $action,
        User $user,
        Organization $organization,
        ?OrganizationQuickAction $orgConfig = null,
    ): bool {
        if (! $action->is_active) {
            return false;
        }

        if ($orgConfig && ! $orgConfig->is_enabled) {
            return false;
        }

        if (! $this->subscriptionService->moduleAllowed($organization, $action->subscription_module)) {
            return false;
        }

        if ($action->permission_slug && ! $user->hasPermission($action->permission_slug, $organization)) {
            return false;
        }

        return Route::has($action->route);
    }

    public function register(array $definition): DashboardQuickAction
    {
        $action = DashboardQuickAction::query()->updateOrCreate(
            [
                'organization_id' => null,
                'action_key' => $definition['action_key'],
            ],
            [
                'module' => $definition['module'],
                'name' => $definition['name'],
                'icon' => $definition['icon'] ?? null,
                'route' => $definition['route'],
                'permission_slug' => $definition['permission_slug'] ?? null,
                'subscription_module' => $definition['subscription_module'] ?? null,
                'sort_order' => $definition['sort_order'] ?? 0,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        event(new QuickActionCreated($action->id, $action->action_key));

        return $action;
    }

    public function updateOrganizationAction(
        Organization $organization,
        DashboardQuickAction $action,
        array $changes,
        ?User $actor = null,
    ): OrganizationQuickAction {
        $record = OrganizationQuickAction::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'quick_action_id' => $action->id,
            ],
            [
                'is_enabled' => $changes['is_enabled'] ?? true,
                'sort_order' => $changes['sort_order'] ?? $action->sort_order,
            ]
        );

        event(new QuickActionUpdated($organization->id, $action->id, $changes, $actor?->id));
        $this->auditLogger->log($record, 'quick_action_updated', $changes, $actor);

        return $record;
    }

    public function seedSystemActions(): void
    {
        if (! Schema::hasTable('dashboard_quick_actions')) {
            return;
        }

        DB::transaction(function () {
            foreach (config('dashboard.quick_actions', []) as $key => $def) {
                $this->register(array_merge($def, ['action_key' => $key]));
            }
        });
    }

    /** @return array<string, mixed> */
    protected function format(DashboardQuickAction $action, ?OrganizationQuickAction $orgConfig): array
    {
        return [
            'id' => $action->id,
            'action_key' => $action->action_key,
            'name' => $action->name,
            'icon' => $action->icon,
            'route' => $action->route,
            'url' => Route::has($action->route) ? route($action->route) : null,
            'module' => $action->module,
            'sort_order' => $orgConfig?->sort_order ?? $action->sort_order,
            'is_system' => $action->is_system,
        ];
    }
}
