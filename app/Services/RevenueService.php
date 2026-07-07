<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;
use App\Models\Payment;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RevenueService
{
    public const AGING_BUCKETS = [
        'current' => 'Current',
        '1_30' => '1–30 Days',
        '31_60' => '31–60 Days',
        '61_90' => '61–90 Days',
        '90_plus' => '90+ Days',
    ];

    /**
     * @return array{
     *     date_from: ?Carbon,
     *     date_to: ?Carbon,
     *     customer_id: ?int,
     *     salesperson_id: ?int,
     *     status: ?string,
     *     period: string
     * }
     */
    public function parseFilters(Request $request): array
    {
        $period = $request->string('period', '30')->toString();
        $period = in_array($period, ['30', '90', '365', 'all', 'custom'], true) ? $period : '30';

        $dateFrom = null;
        $dateTo = null;

        if ($period === 'custom') {
            if ($request->filled('date_from')) {
                $dateFrom = Carbon::parse($request->string('date_from')->toString())->startOfDay();
            }
            if ($request->filled('date_to')) {
                $dateTo = Carbon::parse($request->string('date_to')->toString())->endOfDay();
            }
        } else {
            $dateFrom = match ($period) {
                '90' => Carbon::now()->subDays(90)->startOfDay(),
                '365' => Carbon::now()->subYear()->startOfDay(),
                'all' => null,
                default => Carbon::now()->subDays(30)->startOfDay(),
            };
            $dateTo = $period === 'all' ? null : Carbon::now()->endOfDay();
        }

        $customerId = $request->integer('customer_id') ?: null;
        $salespersonId = $request->integer('salesperson_id') ?: null;
        $status = $request->string('status')->toString() ?: null;

        if ($status && ! array_key_exists($status, config('invoices.statuses', []))) {
            $status = null;
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'customer_id' => $customerId,
            'salesperson_id' => $salespersonId,
            'status' => $status,
            'period' => $period,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dashboardMetrics(Organization $organization, array $filters = []): array
    {
        $invoiceQuery = $this->applyInvoiceFilters(Invoice::query(), $filters);
        $paymentQuery = $this->applyPaymentFilters(Payment::query(), $filters);

        $outstandingQuery = Invoice::query()
            ->where('status', '!=', 'cancelled')
            ->whereRaw('total > amount_paid');

        if ($filters['customer_id'] ?? null) {
            $outstandingQuery->where('customer_id', $filters['customer_id']);
        }

        if ($filters['salesperson_id'] ?? null) {
            $outstandingQuery->where('created_by', $filters['salesperson_id']);
        }

        if ($filters['status'] ?? null) {
            $outstandingQuery->where('status', $filters['status']);
        }

        $outstandingAmount = (float) (clone $outstandingQuery)
            ->selectRaw('COALESCE(SUM(GREATEST(total - amount_paid, 0)), 0) as balance')
            ->value('balance');

        $outstandingCount = (clone $outstandingQuery)->count();

        $totalInvoiced = (float) (clone $invoiceQuery)
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'draft')
            ->sum('total');

        $totalPaid = (float) (clone $paymentQuery)->sum('amount');

        $collectedThisMonth = (float) Payment::query()
            ->whereBetween('payment_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->when($filters['customer_id'] ?? null, fn (Builder $q, int $id) => $q->where('customer_id', $id))
            ->sum('amount');

        $invoiceCount = (clone $invoiceQuery)
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'draft')
            ->count();

        $paidInvoiceCount = (clone $invoiceQuery)->where('status', 'paid')->count();

        $paymentCount = (clone $paymentQuery)->count();

        $averageInvoiceValue = $invoiceCount > 0
            ? Money::round($totalInvoiced / $invoiceCount)
            : 0.0;

        $averagePaymentValue = $paymentCount > 0
            ? Money::round($totalPaid / $paymentCount)
            : 0.0;

        return [
            'currency' => $organization->currency,
            'outstanding_receivables' => $outstandingAmount,
            'outstanding_count' => $outstandingCount,
            'total_paid' => $totalPaid,
            'total_invoiced' => $totalInvoiced,
            'collected_this_month' => $collectedThisMonth,
            'paid_invoice_count' => $paidInvoiceCount,
            'invoice_count' => $invoiceCount,
            'average_invoice_value' => $averageInvoiceValue,
            'average_payment_value' => $averagePaymentValue,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, array{label: string, total: float, count: int}>
     */
    public function invoiceAging(Organization $organization, array $filters = []): array
    {
        $query = Invoice::query()
            ->where('status', '!=', 'cancelled')
            ->whereRaw('total > amount_paid')
            ->select(['id', 'due_date', 'total', 'amount_paid']);

        if ($filters['customer_id'] ?? null) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if ($filters['salesperson_id'] ?? null) {
            $query->where('created_by', $filters['salesperson_id']);
        }

        $buckets = collect(self::AGING_BUCKETS)->mapWithKeys(fn (string $label, string $key) => [
            $key => ['label' => $label, 'total' => 0.0, 'count' => 0],
        ])->all();

        $today = now()->startOfDay();

        foreach ($query->cursor() as $invoice) {
            $balance = Money::balanceDue((float) $invoice->total, (float) $invoice->amount_paid);
            if ($balance <= 0) {
                continue;
            }

            $dueDate = $invoice->due_date?->startOfDay() ?? $today;
            $daysOverdue = $dueDate->lte($today) ? (int) $dueDate->diffInDays($today) : 0;

            if ($daysOverdue === 0) {
                $key = 'current';
            } elseif ($daysOverdue <= 30) {
                $key = '1_30';
            } elseif ($daysOverdue <= 60) {
                $key = '31_60';
            } elseif ($daysOverdue <= 90) {
                $key = '61_90';
            } else {
                $key = '90_plus';
            }

            $buckets[$key]['total'] = round($buckets[$key]['total'] + $balance, 2);
            $buckets[$key]['count']++;
        }

        return $buckets;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function collectionMetrics(Organization $organization, array $filters = []): array
    {
        $invoiceQuery = $this->applyInvoiceFilters(Invoice::query(), $filters)
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'draft');

        $paymentQuery = $this->applyPaymentFilters(Payment::query(), $filters);

        $totalInvoiced = (float) (clone $invoiceQuery)->sum('total');
        $totalPaid = (float) (clone $paymentQuery)->sum('amount');
        $outstanding = max(0, $totalInvoiced - $totalPaid);

        $invoiceCount = (clone $invoiceQuery)->count();
        $paymentCount = (clone $paymentQuery)->count();

        $collectionRate = Money::percentage($totalPaid, $totalInvoiced);

        $paidPercent = $invoiceCount > 0
            ? Money::percentage((clone $invoiceQuery)->where('status', 'paid')->count(), $invoiceCount)
            : null;

        $outstandingPercent = Money::percentage($outstanding, $totalInvoiced);

        $averageDaysToPayment = $this->averageDaysToPayment($filters);

        return [
            'collection_rate' => $collectionRate,
            'outstanding_percent' => $outstandingPercent,
            'paid_percent' => $paidPercent,
            'average_days_to_payment' => $averageDaysToPayment,
            'invoice_count' => $invoiceCount,
            'payment_count' => $paymentCount,
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
            'outstanding' => $outstanding,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function revenueByMonth(Organization $organization, array $filters = [], int $months = 12): Collection
    {
        $results = collect();

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            if ($filters['date_from'] ?? null) {
                if ($end->lt($filters['date_from'])) {
                    continue;
                }
            }

            if ($filters['date_to'] ?? null) {
                if ($start->gt($filters['date_to'])) {
                    continue;
                }
            }

            $query = Payment::query()->whereBetween('payment_date', [$start, $end]);

            if ($filters['customer_id'] ?? null) {
                $query->where('customer_id', $filters['customer_id']);
            }

            if ($filters['salesperson_id'] ?? null) {
                $query->whereHas('invoice', fn (Builder $q) => $q->where('created_by', $filters['salesperson_id']));
            }

            $results->push([
                'label' => $start->format('M Y'),
                'month' => $start->format('Y-m'),
                'total' => (float) $query->sum('amount'),
            ]);
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function revenueByCustomer(Organization $organization, array $filters = [], int $limit = 10): Collection
    {
        $query = $this->applyPaymentFilters(
            Payment::query()
                ->select('customer_id', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as payment_count'))
                ->groupBy('customer_id')
                ->orderByDesc('total'),
            $filters
        );

        $rows = $query->limit($limit)->get();

        $customers = Customer::query()
            ->whereIn('id', $rows->pluck('customer_id')->filter())
            ->get(['id', 'name', 'company'])
            ->keyBy('id');

        return $rows->map(fn ($row) => [
            'customer_id' => $row->customer_id,
            'name' => $customers->get($row->customer_id)?->display_name ?? __('Unknown'),
            'total' => (float) $row->total,
            'payment_count' => (int) $row->payment_count,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function revenueBySalesperson(Organization $organization, array $filters = [], int $limit = 10): Collection
    {
        $query = $this->applyPaymentFilters(Payment::query(), $filters)
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->join('users', 'users.id', '=', 'invoices.created_by')
            ->select(
                'users.id as user_id',
                'users.name',
                DB::raw('SUM(payments.amount) as total'),
                DB::raw('COUNT(payments.id) as payment_count'),
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total');

        return $query->limit($limit)->get()->map(fn ($row) => [
            'user_id' => $row->user_id,
            'name' => $row->name ?? __('Unknown'),
            'total' => (float) $row->total,
            'payment_count' => (int) $row->payment_count,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function revenueByProduct(Organization $organization, array $filters = [], int $limit = 10): Collection
    {
        $query = InvoiceItem::query()
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.status', '!=', 'cancelled')
            ->where('invoices.status', '!=', 'draft')
            ->select(
                'invoice_items.product_id',
                'invoice_items.description',
                DB::raw('SUM(invoice_items.line_total) as total'),
                DB::raw('SUM(invoice_items.quantity) as quantity'),
                DB::raw('COUNT(DISTINCT invoice_items.invoice_id) as invoice_count'),
            )
            ->groupBy('invoice_items.product_id', 'invoice_items.description')
            ->orderByDesc('total');

        if ($filters['date_from'] ?? null) {
            $query->where('invoices.issue_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] ?? null) {
            $query->where('invoices.issue_date', '<=', $filters['date_to']);
        }

        if ($filters['customer_id'] ?? null) {
            $query->where('invoices.customer_id', $filters['customer_id']);
        }

        if ($filters['salesperson_id'] ?? null) {
            $query->where('invoices.created_by', $filters['salesperson_id']);
        }

        if ($filters['status'] ?? null) {
            $query->where('invoices.status', $filters['status']);
        }

        return $query->limit($limit)->get()->map(fn ($row) => [
            'product_id' => $row->product_id,
            'description' => $row->description,
            'total' => (float) $row->total,
            'quantity' => (float) $row->quantity,
            'invoice_count' => (int) $row->invoice_count,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function revenueByOrganization(Organization $organization, array $filters = []): array
    {
        $paymentQuery = $this->applyPaymentFilters(Payment::query(), $filters);
        $invoiceQuery = $this->applyInvoiceFilters(Invoice::query(), $filters)
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'draft');

        return [
            'organization_id' => $organization->id,
            'name' => $organization->name,
            'revenue_collected' => (float) (clone $paymentQuery)->sum('amount'),
            'total_invoiced' => (float) (clone $invoiceQuery)->sum('total'),
            'outstanding' => (float) Invoice::query()
                ->where('status', '!=', 'cancelled')
                ->whereRaw('total > amount_paid')
                ->selectRaw('COALESCE(SUM(GREATEST(total - amount_paid, 0)), 0) as balance')
                ->value('balance'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function customerStatement(Customer $customer): array
    {
        $invoices = Invoice::query()
            ->where('customer_id', $customer->id)
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'draft')
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get(['id', 'number', 'issue_date', 'due_date', 'status', 'total', 'amount_paid', 'currency']);

        $payments = Payment::query()
            ->where('customer_id', $customer->id)
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get(['id', 'number', 'payment_date', 'amount', 'method', 'invoice_id', 'currency']);

        $totalInvoiced = Money::round((float) $invoices->sum('total'));
        $totalPaid = Money::round((float) $payments->sum('amount'));
        $balanceDue = Money::balanceDue($totalInvoiced, $totalPaid);

        $entries = collect();

        foreach ($invoices as $invoice) {
            $entries->push([
                'type' => 'invoice',
                'date' => $invoice->issue_date,
                'number' => $invoice->number,
                'description' => $invoice->number,
                'status' => $invoice->status,
                'debit' => (float) $invoice->total,
                'credit' => 0.0,
                'invoice_id' => $invoice->id,
            ]);
        }

        foreach ($payments as $payment) {
            $entries->push([
                'type' => 'payment',
                'date' => $payment->payment_date,
                'number' => $payment->number,
                'description' => $payment->number,
                'method' => $payment->method,
                'debit' => 0.0,
                'credit' => (float) $payment->amount,
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
            ]);
        }

        $runningBalance = 0.0;
        $ledger = $entries
            ->sortBy(fn ($entry) => [$entry['date']?->format('Y-m-d') ?? '', $entry['type'] === 'invoice' ? 0 : 1])
            ->values()
            ->map(function (array $entry) use (&$runningBalance) {
                $runningBalance = Money::round($runningBalance + $entry['debit'] - $entry['credit']);
                $entry['balance'] = $runningBalance;

                return $entry;
            });

        return [
            'currency' => $invoices->first()?->currency ?? $customer->organization?->currency ?? 'USD',
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
            'balance_due' => $balanceDue,
            'outstanding_balance' => Money::round((float) $invoices->sum(
                fn (Invoice $invoice) => Money::balanceDue((float) $invoice->total, (float) $invoice->amount_paid)
            )),
            'invoices' => $invoices,
            'payments' => $payments,
            'ledger' => $ledger,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function outstandingInvoices(Organization $organization, array $filters = []): Collection
    {
        $query = Invoice::query()
            ->with(['customer:id,name,company', 'creator:id,name'])
            ->where('status', '!=', 'cancelled')
            ->whereRaw('total > amount_paid')
            ->orderBy('due_date');

        if ($filters['customer_id'] ?? null) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if ($filters['salesperson_id'] ?? null) {
            $query->where('created_by', $filters['salesperson_id']);
        }

        if ($filters['status'] ?? null) {
            $query->where('status', $filters['status']);
        }

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function compileFinanceReport(Organization $organization, array $filters = []): array
    {
        $dashboard = $this->dashboardMetrics($organization, $filters);
        $aging = $this->invoiceAging($organization, $filters);
        $collection = $this->collectionMetrics($organization, $filters);
        $revenueByMonth = $this->revenueByMonth($organization, $filters);
        $revenueByCustomer = $this->revenueByCustomer($organization, $filters);

        return [
            ...$dashboard,
            'aging' => $aging,
            'collection' => $collection,
            'revenue_by_month' => $revenueByMonth,
            'revenue_by_customer' => $revenueByCustomer,
            'revenue_by_salesperson' => $this->revenueBySalesperson($organization, $filters),
            'revenue_by_product' => $this->revenueByProduct($organization, $filters),
            'revenue_by_organization' => $this->revenueByOrganization($organization, $filters),
            'charts' => $this->buildChartDatasets($aging, $revenueByMonth, $revenueByCustomer),
        ];
    }

    /**
     * @param  array<string, array{label: string, total: float, count: int}>  $aging
     */
    protected function buildChartDatasets(array $aging, Collection $byMonth, Collection $byCustomer): array
    {
        return [
            'aging' => [
                'labels' => array_column($aging, 'label'),
                'totals' => array_column($aging, 'total'),
                'counts' => array_column($aging, 'count'),
            ],
            'revenue_by_month' => [
                'labels' => $byMonth->pluck('label')->all(),
                'totals' => $byMonth->pluck('total')->all(),
            ],
            'revenue_by_customer' => [
                'labels' => $byCustomer->pluck('name')->all(),
                'totals' => $byCustomer->pluck('total')->all(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function chartDatasets(Organization $organization, array $filters = []): array
    {
        return $this->buildChartDatasets(
            $this->invoiceAging($organization, $filters),
            $this->revenueByMonth($organization, $filters),
            $this->revenueByCustomer($organization, $filters, 8),
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function averageDaysToPayment(array $filters): ?float
    {
        $query = Payment::query()
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->whereNotNull('invoices.issue_date');

        if ($filters['date_from'] ?? null) {
            $query->where('payments.payment_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] ?? null) {
            $query->where('payments.payment_date', '<=', $filters['date_to']);
        }

        if ($filters['customer_id'] ?? null) {
            $query->where('payments.customer_id', $filters['customer_id']);
        }

        if ($filters['salesperson_id'] ?? null) {
            $query->where('invoices.created_by', $filters['salesperson_id']);
        }

        $payments = $query->get(['payments.payment_date', 'invoices.issue_date']);

        if ($payments->isEmpty()) {
            return null;
        }

        $totalDays = $payments->sum(function ($row) {
            $issueDate = Carbon::parse($row->issue_date)->startOfDay();
            $paymentDate = Carbon::parse($row->payment_date)->startOfDay();

            return max(0, (int) $issueDate->diffInDays($paymentDate));
        });

        return round($totalDays / $payments->count(), 1);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyInvoiceFilters(Builder $query, array $filters): Builder
    {
        if ($filters['date_from'] ?? null) {
            $query->where('issue_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] ?? null) {
            $query->where('issue_date', '<=', $filters['date_to']);
        }

        if ($filters['customer_id'] ?? null) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if ($filters['salesperson_id'] ?? null) {
            $query->where('created_by', $filters['salesperson_id']);
        }

        if ($filters['status'] ?? null) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyPaymentFilters(Builder $query, array $filters): Builder
    {
        if ($filters['date_from'] ?? null) {
            $query->where('payment_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] ?? null) {
            $query->where('payment_date', '<=', $filters['date_to']);
        }

        if ($filters['customer_id'] ?? null) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if ($filters['salesperson_id'] ?? null) {
            $query->whereHas('invoice', fn (Builder $q) => $q->where('created_by', $filters['salesperson_id']));
        }

        return $query;
    }
}
