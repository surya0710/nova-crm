<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Task;
use App\Services\TenantContext;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(TenantContext $tenant): View
    {
        $organization = $tenant->get();

        $leadStats = [
            'total' => Lead::query()->count(),
            'open' => Lead::query()->whereNotIn('status', ['won', 'lost'])->count(),
            'won' => Lead::query()->where('status', 'won')->count(),
            'new' => Lead::query()->where('status', 'new')->count(),
        ];

        $customerStats = [
            'total' => Customer::query()->count(),
            'active' => Customer::query()->where('status', 'active')->count(),
        ];

        $productStats = [
            'total' => Product::query()->count(),
            'active' => Product::query()->where('status', 'active')->count(),
        ];

        $recentLeads = Lead::query()
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
            'leadStats' => $leadStats,
            'customerStats' => $customerStats,
            'productStats' => $productStats,
            'taskStats' => $taskStats,
            'recentLeads' => $recentLeads,
            'upcomingTasks' => $upcomingTasks,
        ]);
    }
}
