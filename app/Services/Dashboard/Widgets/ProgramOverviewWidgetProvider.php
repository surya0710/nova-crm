<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use App\Services\TenantContext;

class ProgramOverviewWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'program_overview';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'projects.programs.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $query = Program::query()->whereNull('archived_at');

        $byStatus = (clone $query)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $programs = (clone $query)
            ->with(['portfolio:id,name,code'])
            ->withCount('projects')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get(['id', 'name', 'code', 'status', 'portfolio_id', 'updated_at']);

        return [
            'count' => (clone $query)->count(),
            'by_status' => collect(config('projects.program_statuses', []))->map(fn (string $label, string $status) => [
                'status' => $status,
                'label' => $label,
                'count' => (int) ($byStatus[$status] ?? 0),
            ])->values()->all(),
            'programs' => $programs,
        ];
    }
}
