<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexApiQuotationRequest;
use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Http\Resources\QuotationResource;
use App\Http\Resources\SalesOrderResource;
use App\Models\Quotation;
use App\Services\QuotationConversionService;
use App\Services\QuotationService;
use App\Services\TenantContext;
use App\Support\Api\ApiQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class QuotationController extends Controller
{
    public function __construct(
        protected QuotationService $quotations,
        protected QuotationConversionService $conversion,
    ) {}

    public function index(IndexApiQuotationRequest $request): AnonymousResourceCollection
    {
        $query = Quotation::query()->with(['customer', 'items']);

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

        return QuotationResource::collection(
            $query->latest()->paginate(ApiQuery::perPage($request))->withQueryString()
        );
    }

    public function show(Quotation $quotation): QuotationResource
    {
        $this->authorize('view', $quotation);

        $quotation->load(['customer', 'items.product', 'opportunity']);

        return new QuotationResource($quotation);
    }

    public function store(StoreQuotationRequest $request, TenantContext $tenant): JsonResponse
    {
        $quotation = $this->quotations->create(
            $tenant->get(),
            $request->validated(),
            $request->user(),
        )->load(['customer', 'items']);

        return (new QuotationResource($quotation))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateQuotationRequest $request, Quotation $quotation): QuotationResource
    {
        $quotation = $this->quotations->update(
            $quotation,
            $request->validated(),
            $request->user(),
        )->load(['customer', 'items']);

        return new QuotationResource($quotation);
    }

    public function convert(Request $request, Quotation $quotation): JsonResponse
    {
        $this->authorize('convert', $quotation);

        $existing = $quotation->salesOrder()
            ->where('status', '!=', 'cancelled')
            ->exists();

        try {
            $salesOrder = $this->conversion->convert($quotation, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        $salesOrder->load(['customer', 'items', 'quotation']);

        return (new SalesOrderResource($salesOrder))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode($existing ? 200 : 201);
    }
}
