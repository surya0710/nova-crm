<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\ReportExportService;
use App\Services\ReportService;
use App\Services\RevenueService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, TenantContext $tenant, ReportService $reports): View
    {
        $this->authorizeReports($request);

        $organization = $tenant->get();
        abort_unless($organization, 404);

        $period = $request->string('period', '30')->toString();
        $from = match ($period) {
            '90' => Carbon::now()->subDays(90),
            '365' => Carbon::now()->subYear(),
            'all' => null,
            default => Carbon::now()->subDays(30),
        };
        $groupBy = $request->string('group_by', 'state')->toString();
        $groupBy = in_array($groupBy, ['state', 'country'], true) ? $groupBy : 'state';

        return view('reports.index', [
            'organization' => $organization,
            'data' => $reports->compile($organization, $from, $groupBy, $request->user()),
            'period' => in_array($period, ['30', '90', '365', 'all'], true) ? $period : '30',
            'groupBy' => $groupBy,
        ]);
    }

    public function finance(Request $request, TenantContext $tenant, RevenueService $revenue): View
    {
        $this->authorizeFinance($request);

        $organization = $tenant->get();
        abort_unless($organization, 404);

        $filters = $revenue->parseFilters($request);
        $data = $revenue->compileFinanceReport($organization, $filters);

        return view('reports.finance', [
            'organization' => $organization,
            'data' => $data,
            'filters' => $this->filterFormValues($request, $filters),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'company']),
            'salespeople' => $organization->users()->orderBy('name')->get(['users.id', 'users.name']),
        ]);
    }

    public function exportOutstanding(Request $request, TenantContext $tenant, RevenueService $revenue, ReportExportService $export): StreamedResponse
    {
        $this->authorizeExport($request);

        $organization = $tenant->get();
        abort_unless($organization, 404);

        return $export->exportOutstandingInvoices($organization, $revenue->parseFilters($request));
    }

    public function exportRevenue(Request $request, TenantContext $tenant, RevenueService $revenue, ReportExportService $export): StreamedResponse
    {
        $this->authorizeExport($request);

        $organization = $tenant->get();
        abort_unless($organization, 404);

        return $export->exportRevenueReport($organization, $revenue->parseFilters($request));
    }

    public function exportCustomerStatement(Request $request, Customer $customer, ReportExportService $export): StreamedResponse
    {
        $this->authorizeExport($request);
        $this->authorize('view', $customer);

        return $export->exportCustomerStatement($customer);
    }

    protected function authorizeReports(Request $request): void
    {
        abort_unless(
            $request->user()->hasPermission('reports.view')
            || $request->user()->hasPermission('finance.view'),
            403
        );
    }

    protected function authorizeFinance(Request $request): void
    {
        abort_unless(
            $request->user()->hasPermission('reports.view')
            || $request->user()->hasPermission('finance.view'),
            403
        );
    }

    protected function authorizeExport(Request $request): void
    {
        abort_unless(
            $request->user()->hasPermission('reports.manage')
            || $request->user()->hasPermission('finance.view'),
            403
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function filterFormValues(Request $request, array $filters): array
    {
        return [
            'period' => $filters['period'],
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'customer_id' => $filters['customer_id'] ?? '',
            'salesperson_id' => $filters['salesperson_id'] ?? '',
            'status' => $filters['status'] ?? '',
        ];
    }
}
