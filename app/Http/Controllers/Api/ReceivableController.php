<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexApiReceivableRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\RevenueService;
use App\Services\TenantContext;
use App\Support\Api\ApiQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceivableController extends Controller
{
    public function __construct(protected RevenueService $revenue) {}

    public function index(IndexApiReceivableRequest $request, TenantContext $tenant): JsonResponse
    {
        $organization = $tenant->get();
        $filters = [
            'customer_id' => $request->integer('customer_id') ?: null,
        ];

        $invoices = $this->revenue->outstandingInvoices($organization, $filters);

        if ($status = $request->validated('collection_status')) {
            $invoices = $invoices->filter(fn (Invoice $invoice) => $invoice->collection_status === $status)->values();
        }

        if ($aging = $request->validated('aging')) {
            $invoices = $invoices->filter(fn (Invoice $invoice) => $invoice->agingBucket() === $aging)->values();
        }

        $page = max(1, (int) $request->integer('page', 1));
        $perPage = ApiQuery::perPage($request);
        $slice = $invoices->forPage($page, $perPage)->values();

        return response()->json([
            'data' => InvoiceResource::collection($slice)->resolve(),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $invoices->count(),
            ],
            'metrics' => $this->revenue->dashboardMetrics($organization, $filters),
            'aging' => $this->revenue->invoiceAging($organization, $filters),
        ]);
    }

    public function ledger(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        abort_unless(
            $request->user()?->hasPermission('invoices.view')
            || $request->user()?->hasPermission('finance.view'),
            403
        );

        $statement = $this->revenue->customerStatement($customer);
        unset($statement['invoices'], $statement['payments'], $statement['notes']);

        return response()->json([
            'data' => $statement,
        ]);
    }
}
