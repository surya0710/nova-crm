<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\ProjectHealthSnapshot;
use App\Models\User;
use App\Services\TenantContext;

class ProjectsAtRiskWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'projects_at_risk';
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

        $latestIds = ProjectHealthSnapshot::query()
            ->where('organization_id', $organization->id)
            ->selectRaw('MAX(id) as id')
            ->groupBy('project_id')
            ->pluck('id');

        $snapshots = ProjectHealthSnapshot::query()
            ->whereIn('id', $latestIds)
            ->whereIn('health_status', ['at_risk', 'delayed'])
            ->with(['project:id,name,slug,project_number'])
            ->orderByDesc('calculated_at')
            ->limit(5)
            ->get(['id', 'project_id', 'health_status', 'completion_percentage', 'schedule_variance', 'calculated_at']);

        return [
            'count' => ProjectHealthSnapshot::query()
                ->whereIn('id', $latestIds)
                ->whereIn('health_status', ['at_risk', 'delayed'])
                ->count(),
            'projects' => $snapshots,
        ];
    }
}
