<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\ProjectCollaborationPin;
use App\Models\User;
use App\Services\TenantContext;

class RecentCollaborationWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'recent_collaboration';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'projects.collaboration.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $query = ProjectCollaborationPin::query();

        $pins = (clone $query)
            ->with([
                'project:id,name,slug,project_number',
                'pinnedBy:id,name',
            ])
            ->orderBy('sort_order')
            ->latest()
            ->limit(5)
            ->get();

        return [
            'count' => (clone $query)->count(),
            'pins' => $pins,
        ];
    }
}
