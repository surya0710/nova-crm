<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Task;
use App\Services\Dashboard\WorkspaceService;
use App\Services\LeadVisibilityService;
use App\Services\TenantContext;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        TenantContext $tenant,
        WorkspaceService $workspace,
        LeadVisibilityService $leadVisibility,
    ): View {
        $organization = $tenant->get();
        $user = auth()->user();

        $workspaceData = $workspace->build($user, $organization);

        $leadStats = [
            'total' => $leadVisibility->visibleQuery($user, $organization)->count(),
            'open' => $leadVisibility->visibleQuery($user, $organization)->whereNotIn('status', ['won', 'lost'])->count(),
            'won' => $leadVisibility->visibleQuery($user, $organization)->where('status', 'won')->count(),
            'new' => $leadVisibility->visibleQuery($user, $organization)->where('status', 'new')->count(),
        ];

        $customerStats = [
            'total' => Customer::query()->count(),
            'active' => Customer::query()->where('status', 'active')->count(),
        ];

        $productStats = [
            'total' => Product::query()->count(),
            'active' => Product::query()->where('status', 'active')->count(),
        ];

        $recentLeads = $leadVisibility->visibleQuery($user, $organization)
            ->with('assignee')
            ->latest()
            ->limit(5)
            ->get();

        $taskStats = [
            'open' => Task::query()->whereIn('status', ['pending', 'in_progress'])->count(),
            'overdue' => Task::query()
                ->whereIn('status', ['pending', 'in_progress'])
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->count(),
            'due_today' => Task::query()
                ->whereIn('status', ['pending', 'in_progress'])
                ->whereDate('due_at', today())
                ->count(),
        ];

        $upcomingTasks = Task::query()
            ->with(['assignee', 'taskable'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderByRaw('due_at IS NULL')
            ->orderBy('due_at')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'organization' => $organization,
            'workspace' => $workspaceData,
            'widgets' => $workspaceData['dashboard']['widgets'] ?? [],
            'quickActions' => $workspaceData['quick_actions'] ?? [],
            'notifications' => $workspaceData['notifications'] ?? [],
            'recentActivities' => $workspaceData['recent_activities'] ?? [],
            'leadStats' => $leadStats,
            'customerStats' => $customerStats,
            'productStats' => $productStats,
            'taskStats' => $taskStats,
            'recentLeads' => $recentLeads,
            'upcomingTasks' => $upcomingTasks,
        ]);
    }
}
