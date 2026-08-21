<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\RevenueService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReceivableController extends Controller
{
    public function index(Request $request, TenantContext $tenant, RevenueService $revenue): View
    {
        abort_unless(
            $request->user()?->hasPermission('invoices.view')
            || $request->user()?->hasPermission('finance.view'),
            403
        );

        $organization = $tenant->get();
        abort_unless($organization, 404);

        $filters = [
            'customer_id' => $request->integer('customer_id') ?: null,
            'status' => $request->string('collection_status')->toString() ?: null,
            'aging' => $request->string('aging')->toString() ?: null,
        ];

        $invoices = $revenue->outstandingInvoices($organization, [
            'customer_id' => $filters['customer_id'],
        ]);

        if ($filters['status']) {
            $invoices = $invoices->filter(fn (Invoice $invoice) => $invoice->collection_status === $filters['status'])->values();
        }

        if ($filters['aging']) {
            $invoices = $invoices->filter(fn (Invoice $invoice) => $invoice->agingBucket() === $filters['aging'])->values();
        }

        $aging = $revenue->invoiceAging($organization, ['customer_id' => $filters['customer_id']]);
        $metrics = $revenue->dashboardMetrics($organization, ['customer_id' => $filters['customer_id']]);

        $statusCounts = [
            'paid' => 0,
            'partial' => 0,
            'unpaid' => 0,
            'overdue' => 0,
        ];
        foreach ($invoices as $invoice) {
            $status = $invoice->collection_status;
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }
        }

        return view('receivables.index', [
            'invoices' => $invoices,
            'aging' => $aging,
            'metrics' => $metrics,
            'statusCounts' => $statusCounts,
            'filters' => $filters,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'company']),
            'organization' => $organization,
        ]);
    }
}
