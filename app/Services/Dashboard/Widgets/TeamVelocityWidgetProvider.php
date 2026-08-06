<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use App\Services\TenantContext;

class TeamVelocityWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'team_velocity';
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

        $periodDays = 14;
        $from = now()->subDays($periodDays - 1)->startOfDay();

        $completedCount = Task::query()
            ->where('organization_id', $organization->id)
            ->where('is_archived', false)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $from)
            ->count();

        $weeklyAverage = $periodDays > 0
            ? round($completedCount / ($periodDays / 7), 1)
            : 0.0;

        return [
            'period_days' => $periodDays,
            'completed_count' => $completedCount,
            'weekly_average' => $weeklyAverage,
            'from' => $from->toDateString(),
            'to' => now()->toDateString(),
        ];
    }
}
