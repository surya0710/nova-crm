<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class AnalyticsKpiSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'analytics_kpis';
    }

    public function label(): string
    {
        return __('KPIs');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasAnyPermission(['reports.view', 'finance.view', 'audit.view'])) {
            return collect();
        }

        if (! Route::has('analytics.kpis.index')) {
            return collect();
        }

        $query = trim(mb_strtolower($query));
        if ($query === '') {
            return collect();
        }

        $href = route('analytics.kpis.index');
        $results = collect();

        foreach (config('analytics_kpis.categories', []) as $categoryKey => $category) {
            $categoryLabel = $category['label'] ?? $categoryKey;
            foreach ($category['kpis'] ?? [] as $kpiKey => $kpi) {
                $label = $kpi['label'] ?? $kpiKey;
                $description = $kpi['description'] ?? '';
                $hay = mb_strtolower($label.' '.$description.' '.$categoryLabel.' '.$kpiKey);
                if (! str_contains($hay, $query)) {
                    continue;
                }
                $results->push([
                    'type' => __('KPI'),
                    'label' => $this->label(),
                    'title' => $label,
                    'subtitle' => $categoryLabel,
                    'url' => $href.'#'.rawurlencode($categoryKey.'-'.$kpiKey),
                    'workspace' => 'analytics',
                ]);
            }
        }

        return $results->take($limit)->values();
    }
}
