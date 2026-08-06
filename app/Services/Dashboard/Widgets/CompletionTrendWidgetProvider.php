<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\User;
use App\Services\TaskStatisticsService;
use App\Services\TenantContext;

class CompletionTrendWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'completion_trend';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'projects.statistics.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $stats = app(TaskStatisticsService::class)->forOrganization($organization);

        return [
            'trends' => $stats['trends'],
            'tasks' => [
                'open' => $stats['open'],
                'closed' => $stats['closed'],
                'overdue' => $stats['overdue'],
                'total' => $stats['total'],
            ],
        ];
    }
}
