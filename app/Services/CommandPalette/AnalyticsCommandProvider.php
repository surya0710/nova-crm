<?php

namespace App\Services\CommandPalette;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class AnalyticsCommandProvider implements CommandProviderInterface
{
    public function commands(User $user, ?Organization $organization): Collection
    {
        if (! $organization) {
            return collect();
        }

        $perms = ['reports.view', 'finance.view', 'audit.view', 'projects.reports.view', 'recruitment.reports.view'];
        if (! $user->hasAnyPermission($perms)) {
            return collect();
        }

        $commands = collect();
        $group = __('Analytics');

        if (Route::has('analytics.home')) {
            $commands->push([
                'id' => 'analytics.home',
                'label' => __('Open Analytics'),
                'group' => $group,
                'href' => route('analytics.home'),
                'keywords' => ['analytics', 'insights', 'bi'],
            ]);
        }

        if (Route::has('analytics.executive')) {
            $commands->push([
                'id' => 'analytics.executive',
                'label' => __('Open Executive Dashboard'),
                'group' => $group,
                'href' => route('analytics.executive'),
                'keywords' => ['executive', 'dashboard', 'kpi'],
            ]);
        }

        if (Route::has('analytics.reports.index')) {
            $commands->push([
                'id' => 'analytics.search-reports',
                'label' => __('Search Reports'),
                'group' => $group,
                'href' => route('analytics.reports.index'),
                'keywords' => ['reports', 'search', 'export'],
            ]);
        }

        if (Route::has('analytics.kpis.index')) {
            $commands->push([
                'id' => 'analytics.kpi-library',
                'label' => __('View KPI Library'),
                'group' => $group,
                'href' => route('analytics.kpis.index'),
                'keywords' => ['kpi', 'library', 'metrics'],
            ]);
        }

        if (Route::has('analytics.ai-insights')) {
            $commands->push([
                'id' => 'analytics.ai-insights',
                'label' => __('Open AI Insights'),
                'group' => $group,
                'href' => route('analytics.ai-insights'),
                'keywords' => ['ai', 'insights', 'forecast'],
            ]);
        }

        if (Route::has('analytics.dashboards.index')) {
            $commands->push([
                'id' => 'analytics.dashboards',
                'label' => __('Open Custom Dashboards'),
                'group' => $group,
                'href' => route('analytics.dashboards.index'),
                'keywords' => ['dashboards', 'custom', 'widgets'],
            ]);
        }

        return $commands;
    }
}
