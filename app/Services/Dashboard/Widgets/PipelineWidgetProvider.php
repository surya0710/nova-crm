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
            ->selectRaw('stage, COUNT(*) as count, COALESCE(SUM(amount), 0) as total_value')
            ->groupBy('stage')
            ->get();

        $open = Opportunity::query()->whereIn('stage', config('pipeline.open_stages', []));

        return [
            'stages' => $stages,
            'total_opportunities' => Opportunity::query()->count(),
            'total_value' => (float) Opportunity::query()->sum('amount'),
            'pipeline_value' => (float) (clone $open)->sum('amount'),
            'weighted_pipeline' => (float) (clone $open)
                ->selectRaw('COALESCE(SUM(amount * COALESCE(probability, 0) / 100), 0) as weighted')
                ->value('weighted'),
        ];
    }
}
