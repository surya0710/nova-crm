<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;

class PipelineWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'pipeline';
    }

    public function subscriptionModule(): ?string
    {
        return 'crm';
    }

    public function permissionSlug(): ?string
    {
        return 'opportunities.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $stages = Opportunity::query()
            ->selectRaw('stage, COUNT(*) as count, COALESCE(SUM(value), 0) as total_value')
            ->groupBy('stage')
            ->get();

        return [
            'stages' => $stages,
            'total_opportunities' => Opportunity::query()->count(),
            'total_value' => Opportunity::query()->sum('value'),
        ];
    }
}
