<?php

namespace App\Services\Dashboard;

use App\Events\WorkspaceLoaded;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use App\Services\TenantContext;

class WorkspaceService
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected QuickActionService $quickActionService,
        protected WidgetDataService $widgetDataService,
        protected DashboardCache $cache,
    ) {}

    /** @return array<string, mixed> */
    public function build(User $user, Organization $organization, bool $lazyData = true): array
    {
        return $this->cache->remember(
            'workspace'.($lazyData ? '.lazy' : '.eager'),
            $organization->id,
            $user->id,
            function () use ($user, $organization, $lazyData) {
                $dashboard = $this->dashboardService->build($user, $organization, ! $lazyData);
                $quickActions = $this->quickActionService->available($user, $organization);
                $notifications = $this->recentNotifications($user, $organization);
                $recentActivities = $this->recentActivities($user, $organization);

                event(new WorkspaceLoaded(
                    $organization->id,
                    $user->id,
                    count($dashboard['widgets'] ?? []),
                    $quickActions->count()
                ));

                return [
                    'dashboard' => $dashboard,
                    'quick_actions' => $quickActions->all(),
                    'notifications' => $notifications,
                    'recent_activities' => $recentActivities,
                ];
            }
        );
    }

    /** @return array<string, mixed> */
    protected function recentNotifications(User $user, Organization $organization): array
    {
        $items = $user->notifications()
            ->where('data->organization_id', $organization->id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? '',
                'message' => $n->data['message'] ?? '',
                'read_at' => $n->read_at?->toIso8601String(),
            ]);

        return [
            'items' => $items,
            'unread_count' => $user->unreadNotifications()
                ->where('data->organization_id', $organization->id)
                ->count(),
        ];
    }

    /** @return array<string, mixed> */
    protected function recentActivities(User $user, Organization $organization): array
    {
        app(TenantContext::class)->set($organization);

        $assignedWork = Task::query()
            ->where('assigned_to', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->latest()
            ->limit(5)
            ->get(['id', 'title', 'status', 'due_at']);

        $recentActions = collect();

        if ($user->hasPermission('audit.view', $organization)) {
            $recentActions = AuditLog::query()
                ->latest()
                ->limit(5)
                ->get(['id', 'event', 'subject', 'created_at']);
        }

        return [
            'assigned_work' => $assignedWork,
            'recent_actions' => $recentActions,
        ];
    }
}
