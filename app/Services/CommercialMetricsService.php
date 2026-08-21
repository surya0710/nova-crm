<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Support\Money;
use Illuminate\Support\Collection;

class CommercialMetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function quotationMetrics(?Organization $organization = null): array
    {
        $query = Quotation::query();

        $count = (clone $query)->count();
        $value = (float) (clone $query)->sum('total');
        $accepted = (clone $query)->where('status', 'accepted')->count();
        $acceptedValue = (float) (clone $query)->where('status', 'accepted')->sum('total');
        $converted = (clone $query)->where('status', 'converted')->count();
        $convertedValue = (float) (clone $query)->where('status', 'converted')->sum('total');
        $eligible = $accepted + $converted;
        $conversionRate = Money::percentage((float) $converted, (float) $eligible) ?? 0.0;

        return [
            'count' => $count,
            'value' => $value,
            'accepted_count' => $accepted,
            'accepted_value' => $acceptedValue,
            'converted_count' => $converted,
            'converted_value' => $convertedValue,
            'conversion_rate' => $conversionRate,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function salesOrderMetrics(?Organization $organization = null): array
    {
        $query = SalesOrder::query();
        $active = (clone $query)->where('status', '!=', 'cancelled');

        $count = (clone $active)->count();
        $value = (float) (clone $active)->sum('total');
        $confirmedCount = (clone $query)->where('status', 'confirmed')->count();
        $confirmedValue = (float) (clone $query)->where('status', 'confirmed')->sum('total');
        $invoicedCount = (clone $query)->where('status', '!=', 'cancelled')
            ->whereHas('invoice', fn ($inner) => $inner->where('status', '!=', 'cancelled'))
            ->count();

        return [
            'count' => $count,
            'value' => $value,
            'confirmed_count' => $confirmedCount,
            'confirmed_value' => $confirmedValue,
            'invoiced_count' => $invoicedCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function invoiceMetrics(?Organization $organization = null): array
    {
        $query = Invoice::query();
        $active = (clone $query)->whereNotIn('status', ['cancelled', 'draft']);

        $count = (clone $active)->count();
        $value = (float) (clone $active)->sum('total');
        $paidCount = (clone $query)->where('status', 'paid')->count();
        $paidValue = (float) (clone $query)->where('status', 'paid')->sum('total');

        $outstandingQuery = Invoice::query()
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->whereRaw('total > amount_paid');

        $outstandingCount = (clone $outstandingQuery)->count();
        $outstandingValue = (float) (clone $outstandingQuery)
            ->selectRaw('COALESCE(SUM(GREATEST(total - amount_paid, 0)), 0) as balance')
            ->value('balance');

        $overdueQuery = Invoice::query()
            ->whereIn('status', ['issued', 'partially_paid'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereRaw('total > amount_paid');

        $overdueCount = (clone $overdueQuery)->count();
        $overdueValue = (float) (clone $overdueQuery)
            ->selectRaw('COALESCE(SUM(GREATEST(total - amount_paid, 0)), 0) as balance')
            ->value('balance');

        $revenue = (float) Payment::query()->sum('amount');

        return [
            'count' => $count,
            'value' => $value,
            'paid_count' => $paidCount,
            'paid_value' => $paidValue,
            'outstanding_count' => $outstandingCount,
            'outstanding_value' => $outstandingValue,
            'overdue_count' => $overdueCount,
            'overdue_value' => $overdueValue,
            'revenue' => $revenue,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function receivableMetrics(?Organization $organization = null): array
    {
        $invoices = $this->invoiceMetrics($organization);

        return [
            'outstanding_count' => $invoices['outstanding_count'],
            'outstanding_value' => $invoices['outstanding_value'],
            'overdue_count' => $invoices['overdue_count'],
            'overdue_value' => $invoices['overdue_value'],
            'collected_revenue' => $invoices['revenue'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function revenueBreakdown(RevenueService $revenue, Organization $organization, int $limit = 5): array
    {
        $collection = $revenue->collectionMetrics($organization);

        return [
            'products' => $revenue->revenueByProduct($organization, [], $limit)->values()->all(),
            'customers' => $this->revenueByCustomer($limit),
            'salespeople' => $revenue->revenueBySalesperson($organization, [], $limit)->values()->all(),
            'monthly' => $revenue->revenueByMonth($organization)->values()->all(),
            'collection_rate' => $collection['collection_rate'],
            'total_invoiced' => $collection['total_invoiced'],
            'total_paid' => $collection['total_paid'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function revenueByCustomer(int $limit = 5): array
    {
        return Invoice::query()
            ->with('customer:id,name,company')
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->selectRaw('customer_id, COALESCE(SUM(amount_paid), 0) as revenue, COALESCE(SUM(total), 0) as invoiced')
            ->groupBy('customer_id')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn (Invoice $row) => [
                'customer_id' => $row->customer_id,
                'name' => $row->customer?->display_name,
                'revenue' => (float) $row->revenue,
                'invoiced' => (float) $row->invoiced,
            ])
            ->all();
    }
}
