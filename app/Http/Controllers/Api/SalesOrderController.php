<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexApiSalesOrderRequest;
use App\Http\Requests\StoreSalesOrderRequest;
use App\Http\Requests\UpdateSalesOrderRequest;
use App\Http\Resources\CommercialLineItemResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\SalesOrderResource;
use App\Models\SalesOrder;
use App\Services\SalesOrderConversionService;
use App\Services\SalesOrderService;
use App\Services\TenantContext;
use App\Support\Api\ApiQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class SalesOrderController extends Controller
{
    public function __construct(
        protected SalesOrderService $salesOrders,
        protected SalesOrderConversionService $conversion,
    ) {}

    public function index(IndexApiSalesOrderRequest $request): AnonymousResourceCollection
    {
        $query = SalesOrder::query()->with(['customer', 'items']);

        if ($search = $request->validated('search')) {
            $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($status = $request->validated('status')) {
            $query->where('status', $status);
        }

        if ($customerId = $request->integer('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        return SalesOrderResource::collection(
            $query->latest()->paginate(ApiQuery::perPage($request))->withQueryString()
        );
    }

    public function show(SalesOrder $salesOrder): SalesOrderResource
    {
        $this->authorize('view', $salesOrder);

        $salesOrder->load(['customer', 'items.product', 'opportunity', 'quotation']);

        return new SalesOrderResource($salesOrder);
    }

    public function store(StoreSalesOrderRequest $request, TenantContext $tenant): JsonResponse
    {
        $salesOrder = $this->salesOrders->create(
            $tenant->get(),
            $request->validated(),
            $request->user(),
        )->load(['customer', 'items']);

        return (new SalesOrderResource($salesOrder))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateSalesOrderRequest $request, SalesOrder $salesOrder): SalesOrderResource
    {
        $salesOrder = $this->salesOrders->update(
            $salesOrder,
            $request->validated(),
            $request->user(),
        )->load(['customer', 'items']);

        return new SalesOrderResource($salesOrder);
    }

    public function items(SalesOrder $salesOrder): AnonymousResourceCollection
    {
        $this->authorize('view', $salesOrder);

        return CommercialLineItemResource::collection(
            $salesOrder->items()->orderBy('sort_order')->get()
        );
    }

    public function convert(Request $request, SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('convert', $salesOrder);

        $existing = $salesOrder->invoice()
            ->where('status', '!=', 'cancelled')
            ->exists();

        try {
            $invoice = $this->conversion->convert($salesOrder, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        $invoice->load(['customer', 'items', 'quotation', 'salesOrder']);

        return (new InvoiceResource($invoice))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode($existing ? 200 : 201);
    }
}
