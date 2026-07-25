<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\User;
use App\Services\ProjectHealthService;
use App\Services\TenantContext;

class ProjectHealthWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'project_health';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'projects.health.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $summary = app(ProjectHealthService::class)->portfolioSummary($organization);
        $total = array_sum($summary);

        return [
            'total' => $total,
            'statuses' => collect($summary)->map(fn (int $count, string $status) => [
                'status' => $status,
                'label' => config('projects.health_statuses.'.$status, ucfirst(str_replace('_', ' ', $status))),
                'count' => $count,
            ])->values()->all(),
        ];
    }
}
