<?php

namespace App\Services\Platform;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlatformReportService
{
    public function compile(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->subMonths(11)->startOfMonth();
        $to ??= now()->endOfMonth();

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'organizations_growth' => $this->organizationsGrowth($from, $to),
            'users_growth' => $this->usersGrowth($from, $to),
            'lead_volume' => $this->leadVolume($from, $to),
            'customer_growth' => $this->customerGrowth($from, $to),
            'revenue_managed' => $this->revenueManaged($from, $to),
            'invoices' => $this->invoiceStats($from, $to),
            'payments' => $this->paymentStats($from, $to),
            'top_active_organizations' => $this->topActiveOrganizations(),
            'storage_usage' => Organization::query()
                ->select('name', 'storage_used_bytes', 'plan')
                ->orderByDesc('storage_used_bytes')
                ->limit(10)
                ->get(),
            'api_usage' => null,
        ];
    }

    public function toCsv(array $report): string
    {
        $lines = ['Section,Period,Value'];

        foreach ($report['organizations_growth'] as $row) {
            $lines[] = "Organizations Growth,{$row['period']},{$row['count']}";
        }

        foreach ($report['users_growth'] as $row) {
            $lines[] = "Users Growth,{$row['period']},{$row['count']}";
        }

        foreach ($report['lead_volume'] as $row) {
            $lines[] = "Lead Volume,{$row['period']},{$row['count']}";
        }

        foreach ($report['customer_growth'] as $row) {
            $lines[] = "Customer Growth,{$row['period']},{$row['count']}";
        }

        $lines[] = "Revenue Managed,{$report['period']['from']} to {$report['period']['to']},{$report['revenue_managed']['total']}";

        return implode("\n", $lines);
    }

    protected function organizationsGrowth(Carbon $from, Carbon $to): Collection
    {
        return Organization::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period, count(*) as count")
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    protected function usersGrowth(Carbon $from, Carbon $to): Collection
    {
        return DB::table('organization_user')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period, count(distinct user_id) as count")
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    protected function leadVolume(Carbon $from, Carbon $to): Collection
    {
        return Lead::withoutGlobalScopes()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period, count(*) as count")
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    protected function customerGrowth(Carbon $from, Carbon $to): Collection
    {
        return Customer::withoutGlobalScopes()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period, count(*) as count")
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    protected function revenueManaged(Carbon $from, Carbon $to): array
    {
        $total = (float) Payment::withoutGlobalScopes()
            ->whereBetween('payment_date', [$from, $to])
            ->sum('amount');

        return ['total' => $total];
    }

    protected function invoiceStats(Carbon $from, Carbon $to): array
    {
        return [
            'total' => Invoice::withoutGlobalScopes()
                ->whereBetween('created_at', [$from, $to])
                ->count(),
            'issued' => Invoice::withoutGlobalScopes()
                ->whereBetween('created_at', [$from, $to])
                ->where('status', 'issued')
                ->count(),
        ];
    }

    protected function paymentStats(Carbon $from, Carbon $to): array
    {
        return [
            'count' => Payment::withoutGlobalScopes()
                ->whereBetween('payment_date', [$from, $to])
                ->count(),
            'total' => (float) Payment::withoutGlobalScopes()
                ->whereBetween('payment_date', [$from, $to])
                ->sum('amount'),
        ];
    }

    protected function topActiveOrganizations(): Collection
    {
        return Organization::query()
            ->leftJoin('audit_logs', 'audit_logs.organization_id', '=', 'organizations.id')
            ->where('audit_logs.created_at', '>=', now()->subDays(30))
            ->select('organizations.id', 'organizations.name', 'organizations.plan')
            ->selectRaw('count(audit_logs.id) as activity_count')
            ->groupBy('organizations.id', 'organizations.name', 'organizations.plan')
            ->orderByDesc('activity_count')
            ->limit(10)
            ->get();
    }
}
