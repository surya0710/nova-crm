<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\ProjectRisk;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Support\Carbon;

class UpcomingRisksWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'upcoming_risks';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'projects.risks.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $days = (int) ($configuration['days'] ?? 14);
        $until = Carbon::today()->addDays($days);

        $query = ProjectRisk::query()
            ->whereNotIn('status', ['closed'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $until)
            ->with([
                'project:id,name,slug,project_number',
                'portfolio:id,name,code',
                'owner:id,name',
            ])
            ->orderBy('due_date')
            ->orderByDesc('severity');

        $risks = (clone $query)->limit(8)->get();

        return [
            'days' => $days,
            'count' => (clone $query)->count(),
            'risks' => $risks,
        ];
    }
}
