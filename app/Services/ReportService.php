<?php

namespace App\Services;

use App\Models\Customer;
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
    public function __construct(
        protected RevenueService $revenue,
        protected LeadVisibilityService $leadVisibility,
    ) {}

    public function compile(Organization $organization, ?Carbon $from = null, string $groupBy = 'state', ?User $user = null): array
    {
        $groupBy = in_array($groupBy, ['state', 'country'], true) ? $groupBy : 'state';
        $user ??= auth()->user();
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

        $visibleLeads = fn () => $user
            ? $this->leadVisibility->visibleQuery($user, $organization)
            : Lead::query();

        $leadCounts = $visibleLeads()
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

        $topPerformers = $visibleLeads()
            ->where('status', 'won')
            ->whereNotNull('assigned_to')
            ->select('assigned_to', DB::raw('count(*) as won_count'))
            ->groupBy('assigned_to')
            ->orderByDesc('won_count')
            ->limit(5)
            ->get();

        $users = User::query()
            ->whereIn('id', $topPerformers->pluck('assigned_to')->filter())
            ->get()
            ->keyBy('id');

        $topPerformers = $topPerformers->map(fn ($row) => [
            'name' => $users->get($row->assigned_to)?->name ?? __('Unknown'),
            'count' => (int) $row->won_count,
        ]);

        $leadGeography = $visibleLeads()
            ->whereNotNull($groupBy)
            ->where($groupBy, '!=', '')
            ->select($groupBy, DB::raw('count(*) as count'))
            ->groupBy($groupBy)
            ->orderByDesc('count')
            ->get();

        $customerGeography = Customer::query()
            ->whereNotNull($groupBy)
            ->where($groupBy, '!=', '')
            ->select($groupBy, DB::raw('count(*) as count'))
            ->groupBy($groupBy)
            ->orderByDesc('count')
            ->get();

        $revenueGeography = (clone $paymentQuery)
            ->join('customers', 'customers.id', '=', 'payments.customer_id')
            ->where('customers.organization_id', $organization->id)
            ->whereNotNull('customers.'.$groupBy)
            ->where('customers.'.$groupBy, '!=', '')
            ->select(
                'customers.'.$groupBy.' as geography',
                DB::raw('sum(payments.amount) as total'),
            )
            ->groupBy('customers.'.$groupBy)
            ->orderByDesc('total')
            ->get();

        $conversionGeography = $visibleLeads()
            ->whereNotNull($groupBy)
            ->where($groupBy, '!=', '')
            ->select(
                $groupBy,
                DB::raw('count(*) as total'),
                DB::raw("sum(case when status = 'converted' or converted_at is not null then 1 else 0 end) as converted"),
            )
            ->groupBy($groupBy)
            ->orderByDesc('converted')
            ->get()
            ->map(function ($row) use ($groupBy) {
                $total = (int) $row->total;
                $converted = (int) $row->converted;

                return [
                    'geography' => (string) $row->{$groupBy},
                    'total' => $total,
                    'converted' => $converted,
                    'rate' => $total > 0 ? round(($converted / $total) * 100, 1) : 0.0,
                ];
            });

        return [
            'currency' => $organization->currency,
            'revenue_collected' => $revenueCollected,
            'outstanding_amount' => (float) $outstandingAmount,
            'outstanding_count' => $outstandingCount,
            'lead_counts' => $leadCounts,
            'conversion_rate' => $conversionRate,
            'lead_total' => $visibleLeads()->count(),
            'opportunity_by_stage' => $opportunityByStage,
            'open_pipeline_value' => $openPipelineValue,
            'invoice_counts' => $invoiceCounts,
            'quotation_counts' => $quotationCounts,
            'monthly_revenue' => $this->monthlyRevenue(),
            'payments_by_method' => $paymentsByMethod,
            'top_performers' => $topPerformers,
            'geographic_group' => $groupBy,
            'leads_by_geography' => $leadGeography,
            'customers_by_geography' => $customerGeography,
            'revenue_by_geography' => $revenueGeography,
            'lead_conversion_by_geography' => $conversionGeography,
        ];
    }

    protected function monthlyRevenue(): Collection
    {
        $rangeStart = now()->subMonths(5)->startOfMonth();

        $payments = Payment::query()
            ->where('payment_date', '>=', $rangeStart)
            ->get(['payment_date', 'amount']);

        $totalsByMonth = $payments->groupBy(
            fn (Payment $payment) => $payment->payment_date->format('Y-m')
        )->map(fn (Collection $group) => (float) $group->sum('amount'));

        $months = collect();

        for ($i = 5; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $key = $start->format('Y-m');

            $months->push([
                'label' => $start->format('M Y'),
                'total' => (float) ($totalsByMonth[$key] ?? 0),
            ]);
        }

        return $months;
    }
}
