<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class AnalyticsViewSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'analytics_views';
    }

    public function label(): string
    {
        return __('Analytics');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasAnyPermission(['reports.view', 'finance.view', 'audit.view', 'projects.reports.view', 'recruitment.reports.view'])) {
            return collect();
        }

        $query = trim(mb_strtolower($query));
        if ($query === '') {
            return collect();
        }

        $views = collect([
            ['title' => __('Analytics Overview'), 'subtitle' => __('Workspace home'), 'route' => 'analytics.home', 'keywords' => 'analytics overview home'],
            ['title' => __('Executive Dashboard'), 'subtitle' => __('Cross-module KPIs'), 'route' => 'analytics.executive', 'keywords' => 'executive dashboard'],
            ['title' => __('CRM Analytics'), 'subtitle' => __('Pipeline and revenue'), 'route' => 'analytics.crm', 'keywords' => 'crm sales pipeline'],
            ['title' => __('Project Analytics'), 'subtitle' => __('Delivery health'), 'route' => 'analytics.projects', 'keywords' => 'projects delivery'],
            ['title' => __('HR Analytics'), 'subtitle' => __('People metrics'), 'route' => 'analytics.hr', 'keywords' => 'hr people headcount'],
            ['title' => __('AI Insights'), 'subtitle' => __('Forecasts and risks'), 'route' => 'analytics.ai-insights', 'keywords' => 'ai insights forecast'],
            ['title' => __('Custom Dashboards'), 'subtitle' => __('Dashboard templates'), 'route' => 'analytics.dashboards.index', 'keywords' => 'dashboards custom widgets'],
            ['title' => __('KPI Library'), 'subtitle' => __('Shared KPI definitions'), 'route' => 'analytics.kpis.index', 'keywords' => 'kpi library metrics thresholds'],
            ['title' => __('Reports Center'), 'subtitle' => __('Saved and scheduled reports'), 'route' => 'analytics.reports.index', 'keywords' => 'reports center export'],
            ['title' => __('Sales Reports'), 'subtitle' => __('CRM report catalog'), 'route' => 'reports.index', 'keywords' => 'sales reports'],
            ['title' => __('Finance Reports'), 'subtitle' => __('AR and revenue'), 'route' => 'reports.finance', 'keywords' => 'finance ar revenue'],
        ]);

        return $views
            ->filter(function (array $view) use ($query) {
                $hay = mb_strtolower($view['title'].' '.$view['subtitle'].' '.$view['keywords']);

                return str_contains($hay, $query) && Route::has($view['route']);
            })
            ->take($limit)
            ->map(fn (array $view) => [
                'type' => __('Analytics View'),
                'label' => $this->label(),
                'title' => $view['title'],
                'subtitle' => $view['subtitle'],
                'url' => route($view['route']),
                'workspace' => 'analytics',
            ])
            ->values();
    }
}
