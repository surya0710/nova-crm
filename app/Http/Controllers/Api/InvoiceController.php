<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexApiInvoiceRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\TenantContext;
use App\Support\Api\ApiQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceService $invoices) {}

    public function index(IndexApiInvoiceRequest $request): AnonymousResourceCollection
    {
        $query = Invoice::query()->with(['customer', 'items']);

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

        return InvoiceResource::collection(
            $query->latest()->paginate(ApiQuery::perPage($request))->withQueryString()
        );
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'items.product', 'quotation']);

        return new InvoiceResource($invoice);
    }

    public function store(StoreInvoiceRequest $request, TenantContext $tenant): JsonResponse
    {
        $invoice = $this->invoices->create(
            $tenant->get(),
            $request->validated(),
            $request->user(),
        )->load(['customer', 'items']);

        return (new InvoiceResource($invoice))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse|InvoiceResource
    {
        try {
            $invoice = $this->invoices->update(
                $invoice,
                $request->validated(),
                $request->user(),
            )->load(['customer', 'items']);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return new InvoiceResource($invoice);
    }
}
