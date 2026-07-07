<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(protected RevenueService $revenue) {}

    public function compile(Organization $organization, ?Carbon $from = null): array
    {
        $filters = [
            'date_from' => $from,
            'date_to' => null,
            'customer_id' => null,
            'salesperson_id' => null,
            'status' => null,
            'period' => $from ? 'custom' : 'all',
        ];

        $financeSummary = $this->revenue->dashboardMetrics($organization, $filters);

        $paymentQuery = Payment::query();
        if ($from) {
            $paymentQuery->where('payment_date', '>=', $from);
        }

        $revenueCollected = (float) (clone $paymentQuery)->sum('amount');
        $outstandingAmount = $financeSummary['outstanding_receivables'];
        $outstandingCount = $financeSummary['outstanding_count'];

        $leadCounts = Lead::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $won = (int) ($leadCounts['won'] ?? 0);
        $lost = (int) ($leadCounts['lost'] ?? 0);
        $closed = $won + $lost;
        $conversionRate = $closed > 0 ? round(($won / $closed) * 100, 1) : null;

        $opportunityByStage = Opportunity::query()
            ->select('stage', DB::raw('count(*) as count'), DB::raw('coalesce(sum(amount), 0) as value'))
            ->groupBy('stage')
            ->get()
            ->keyBy('stage');

        $openPipelineValue = (float) Opportunity::query()
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->sum('amount');

        $invoiceCounts = Invoice::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $quotationCounts = Quotation::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $paymentsByMethod = (clone $paymentQuery)
            ->select('method', DB::raw('sum(amount) as total'), DB::raw('count(*) as count'))
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();

        $topPerformers = Lead::query()
            ->where('status', 'won')
            ->whereNotNull('assigned_to')
            ->select('assigned_to', DB::raw('count(*) as won_count'))
            ->groupBy('assigned_to')
            ->orderByDesc('won_count')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $user = User::find($row->assigned_to);

                return [
                    'name' => $user?->name ?? __('Unknown'),
                    'count' => (int) $row->won_count,
                ];
            });

        return [
            'currency' => $organization->currency,
            'revenue_collected' => $revenueCollected,
            'outstanding_amount' => (float) $outstandingAmount,
            'outstanding_count' => $outstandingCount,
            'lead_counts' => $leadCounts,
            'conversion_rate' => $conversionRate,
            'lead_total' => Lead::query()->count(),
            'opportunity_by_stage' => $opportunityByStage,
            'open_pipeline_value' => $openPipelineValue,
            'invoice_counts' => $invoiceCounts,
            'quotation_counts' => $quotationCounts,
            'monthly_revenue' => $this->monthlyRevenue(),
            'payments_by_method' => $paymentsByMethod,
            'top_performers' => $topPerformers,
        ];
    }

    protected function monthlyRevenue(): Collection
    {
        $months = collect();

        for ($i = 5; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            $months->push([
                'label' => $start->format('M Y'),
                'total' => (float) Payment::query()
                    ->whereBetween('payment_date', [$start, $end])
                    ->sum('amount'),
            ]);
        }

        return $months;
    }
}
