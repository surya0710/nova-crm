<?php

namespace App\Services\Platform;

use App\Models\PlatformUser;
use Illuminate\Support\Facades\Route;

class PlatformWorkspaceHomeService
{
    public function __construct(
        protected PlatformDashboardService $dashboard,
    ) {}

    public function build(PlatformUser $user): array
    {
        $metrics = $this->dashboard->metrics();
        $layout = $user->dashboardLayout();

        $widgets = collect($layout)
            ->filter(fn ($key) => in_array($key, config('platform.dashboard_widgets', []), true))
            ->values()
            ->all();

        if ($widgets === []) {
            $widgets = config('platform.dashboard_widgets', []);
        }

        return [
            'metrics' => $metrics,
            'widgets' => $widgets,
            'availableWidgets' => config('platform.dashboard_widgets', []),
            'kpis' => $this->kpis($metrics),
            'quickActions' => $this->quickActions($user),
            'alerts' => $this->alerts($metrics),
        ];
    }

    protected function kpis(array $metrics): array
    {
        return [
            [
                'label' => __('Total Organizations'),
                'value' => number_format($metrics['organizations']['total']),
                'hint' => __(':count active', ['count' => $metrics['organizations']['active']]),
            ],
            [
                'label' => __('Active Users'),
                'value' => number_format($metrics['users']['active_today']),
                'hint' => __(':count MAU', ['count' => number_format($metrics['users']['mau'])]),
            ],
            [
                'label' => __('Revenue (MTD)'),
                'value' => '$'.number_format($metrics['revenue']['month'], 2),
                'hint' => __('Succeeded transactions'),
            ],
            [
                'label' => __('Queue Health'),
                'value' => ucfirst($metrics['queue']['health']),
                'hint' => __(':failed failed · :pending pending', [
                    'failed' => $metrics['queue']['failed'],
                    'pending' => $metrics['queue']['pending'],
                ]),
            ],
        ];
    }

    protected function quickActions(PlatformUser $user): array
    {
        $actions = [];

        if ($user->hasPermission('platform.organizations.manage') && Route::has('platform.organizations.create')) {
            $actions[] = [
                'label' => __('Create Organization'),
                'href' => route('platform.organizations.create'),
            ];
        }

        if ($user->hasPermission('platform.organizations.view') && Route::has('platform.organizations.index')) {
            $actions[] = [
                'label' => __('Open Organizations'),
                'href' => route('platform.organizations.index'),
            ];
        }

        if ($user->hasPermission('platform.subscriptions.view') && Route::has('platform.subscriptions.index')) {
            $actions[] = [
                'label' => __('Open Subscriptions'),
                'href' => route('platform.subscriptions.index'),
            ];
        }

        if ($user->hasPermission('platform.monitoring.view') && Route::has('platform.monitoring.index')) {
            $actions[] = [
                'label' => __('Open Monitoring'),
                'href' => route('platform.monitoring.index'),
            ];
        }

        if ($user->hasPermission('platform.providers.view') && Route::has('platform.providers.index')) {
            $actions[] = [
                'label' => __('Open Providers'),
                'href' => route('platform.providers.index'),
            ];
        }

        if ($user->hasPermission('platform.support.view') && Route::has('platform.support.index')) {
            $actions[] = [
                'label' => __('Open Support'),
                'href' => route('platform.support.index'),
            ];
        }

        return $actions;
    }

    protected function alerts(array $metrics): array
    {
        $items = [];

        if (($metrics['alerts']['failed_jobs'] ?? 0) > 0) {
            $items[] = [
                'label' => __(':count failed background jobs', ['count' => $metrics['alerts']['failed_jobs']]),
                'href' => Route::has('platform.monitoring.index') ? route('platform.monitoring.index') : null,
                'tone' => 'warning',
            ];
        }

        if (($metrics['alerts']['expired_orgs'] ?? 0) > 0) {
            $items[] = [
                'label' => __(':count expired organizations', ['count' => $metrics['alerts']['expired_orgs']]),
                'href' => Route::has('platform.subscriptions.trials') ? route('platform.subscriptions.trials') : null,
                'tone' => 'danger',
            ];
        }

        if (($metrics['alerts']['open_tickets'] ?? 0) > 0) {
            $items[] = [
                'label' => __(':count open support tickets', ['count' => $metrics['alerts']['open_tickets']]),
                'href' => Route::has('platform.support.index') ? route('platform.support.index') : null,
                'tone' => 'info',
            ];
        }

        if (($metrics['alerts']['maintenance'] ?? 0) > 0) {
            $items[] = [
                'label' => __('Active maintenance notice'),
                'href' => Route::has('platform.support.index') ? route('platform.support.index') : null,
                'tone' => 'warning',
            ];
        }

        return $items;
    }
}
