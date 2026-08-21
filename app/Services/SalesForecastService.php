<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\SalesTarget;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SalesForecastService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(?Organization $organization = null, ?int $year = null, ?int $month = null): array
    {
        $year ??= (int) now()->year;
        $month ??= (int) now()->month;
        $openStages = config('pipeline.open_stages', []);

        $open = Opportunity::query()->whereIn('stage', $openStages);
        $won = Opportunity::query()->where('stage', 'closed_won');
        $lost = Opportunity::query()->where('stage', 'closed_lost');
        $periodWon = (clone $won)
            ->whereYear('won_at', $year)
            ->when($month, fn ($q) => $q->whereMonth('won_at', $month));

        $openCount = (clone $open)->count();
        $openValue = (float) (clone $open)->sum('amount');
        $weighted = (float) (clone $open)
            ->selectRaw('COALESCE(SUM(amount * COALESCE(probability, 0) / 100), 0) as weighted')
            ->value('weighted');
        $wonCount = (clone $won)->count();
        $wonValue = (float) (clone $won)->sum('amount');
        $lostCount = (clone $lost)->count();
        $lostValue = (float) (clone $lost)->sum('amount');
        $closedCount = $wonCount + $lostCount;

        $avgDeal = $wonCount > 0 ? round($wonValue / $wonCount, 2) : 0.0;
        $avgCycle = (float) (clone $won)
            ->whereNotNull('won_at')
            ->selectRaw('COALESCE(AVG(DATEDIFF(won_at, created_at)), 0) as days')
            ->value('days');

        $periodWonValue = (float) $periodWon->sum('amount');
        $target = $this->targetFor($year, $month);

        return [
            'pipeline_value' => $openValue,
            'weighted_pipeline' => round($weighted, 2),
            'expected_revenue' => round($weighted, 2),
            'open_count' => $openCount,
            'won_count' => $wonCount,
            'won_value' => $wonValue,
            'lost_count' => $lostCount,
            'lost_value' => $lostValue,
            'win_rate' => $closedCount > 0 ? round(($wonCount / $closedCount) * 100, 1) : 0.0,
            'average_deal_size' => $avgDeal,
            'average_sales_cycle_days' => round($avgCycle, 1),
            'revenue_by_stage' => $this->revenueByStage(),
            'revenue_by_salesperson' => $this->revenueBySalesperson(),
            'monthly_forecast' => $this->monthlyForecast($year),
            'period' => ['year' => $year, 'month' => $month],
            'target_amount' => $target,
            'achievement_value' => $periodWonValue,
            'achievement_percent' => $target > 0 ? round(($periodWonValue / $target) * 100, 1) : null,
        ];
    }

    /**
     * @return array<string, array{count: int, value: float}>
     */
    public function revenueByStage(): array
    {
        $rows = Opportunity::query()
            ->selectRaw('stage, COUNT(*) as total, COALESCE(SUM(amount), 0) as value')
            ->groupBy('stage')
            ->get();

        $result = [];
        foreach (array_keys(config('pipeline.stages', [])) as $stage) {
            $row = $rows->firstWhere('stage', $stage);
            $result[$stage] = [
                'count' => (int) ($row->total ?? 0),
                'value' => (float) ($row->value ?? 0),
            ];
        }

        return $result;
    }

    /**
     * @return list<array{user_id: ?int, name: string, won_value: float, open_value: float}>
     */
    public function revenueBySalesperson(): array
    {
        $won = Opportunity::query()
            ->with('assignee')
            ->where('stage', 'closed_won')
            ->selectRaw('assigned_to, COALESCE(SUM(amount), 0) as won_value')
            ->groupBy('assigned_to')
            ->get()
            ->keyBy('assigned_to');

        $open = Opportunity::query()
            ->whereIn('stage', config('pipeline.open_stages', []))
            ->selectRaw('assigned_to, COALESCE(SUM(amount), 0) as open_value')
            ->groupBy('assigned_to')
            ->get()
            ->keyBy('assigned_to');

        $ids = $won->keys()->merge($open->keys())->unique();

        return $ids->map(function ($id) use ($won, $open) {
            $wonRow = $won->get($id);
            $openRow = $open->get($id);
            $user = $wonRow?->assignee ?? $openRow?->assignee ?? null;

            return [
                'user_id' => $id ? (int) $id : null,
                'name' => $user?->name ?? __('Unassigned'),
                'won_value' => (float) ($wonRow->won_value ?? 0),
                'open_value' => (float) ($openRow->open_value ?? 0),
            ];
        })->values()->all();
    }

    /**
     * @return list<array{month: string, expected: float, won: float}>
     */
    public function monthlyForecast(int $year): array
    {
        $expected = Opportunity::query()
            ->whereIn('stage', config('pipeline.open_stages', []))
            ->whereNotNull('expected_close_date')
            ->whereYear('expected_close_date', $year)
            ->selectRaw('MONTH(expected_close_date) as month_num, COALESCE(SUM(amount * COALESCE(probability, 0) / 100), 0) as expected')
            ->groupBy('month_num')
            ->pluck('expected', 'month_num');

        $won = Opportunity::query()
            ->where('stage', 'closed_won')
            ->whereNotNull('won_at')
            ->whereYear('won_at', $year)
            ->selectRaw('MONTH(won_at) as month_num, COALESCE(SUM(amount), 0) as won')
            ->groupBy('month_num')
            ->pluck('won', 'month_num');

        return collect(range(1, 12))->map(function (int $month) use ($year, $expected, $won) {
            return [
                'month' => Carbon::createFromDate($year, $month, 1)->format('Y-m'),
                'expected' => round((float) ($expected[$month] ?? 0), 2),
                'won' => round((float) ($won[$month] ?? 0), 2),
            ];
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function pipelineSummary(): array
    {
        $summary = $this->summary();

        return [
            'open_count' => $summary['open_count'],
            'open_value' => $summary['pipeline_value'],
            'weighted_value' => $summary['weighted_pipeline'],
            'won_count' => $summary['won_count'],
            'won_value' => $summary['won_value'],
            'lost_count' => $summary['lost_count'],
            'lost_value' => $summary['lost_value'],
            'win_rate' => $summary['win_rate'],
            'average_deal_size' => $summary['average_deal_size'],
            'average_sales_cycle_days' => $summary['average_sales_cycle_days'],
        ];
    }

    protected function targetFor(int $year, int $month): float
    {
        $monthly = SalesTarget::query()
            ->whereNull('user_id')
            ->where('year', $year)
            ->where('month', $month)
            ->value('amount');

        if ($monthly !== null) {
            return (float) $monthly;
        }

        return (float) SalesTarget::query()
            ->whereNull('user_id')
            ->where('year', $year)
            ->whereNull('month')
            ->value('amount');
    }
}
