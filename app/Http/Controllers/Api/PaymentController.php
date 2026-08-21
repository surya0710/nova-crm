<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexApiPaymentRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\TenantContext;
use App\Support\Api\ApiQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $payments) {}

    public function index(IndexApiPaymentRequest $request): AnonymousResourceCollection
    {
        $query = Payment::query()->with(['customer', 'invoice']);

        if ($search = $request->validated('search')) {
            $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        if ($method = $request->validated('method')) {
            $query->where('method', $method);
        }

        if ($invoiceId = $request->integer('invoice_id')) {
            $query->where('invoice_id', $invoiceId);
        }

        if ($customerId = $request->integer('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        return PaymentResource::collection(
            $query->latest('payment_date')->latest()->paginate(ApiQuery::perPage($request))->withQueryString()
        );
    }

    public function show(Payment $payment): PaymentResource
    {
        $this->authorize('view', $payment);

        return new PaymentResource($payment->load(['customer', 'invoice']));
    }

    public function store(StorePaymentRequest $request, TenantContext $tenant): JsonResponse
    {
        return $this->recordAgainstInvoice(
            Invoice::query()->findOrFail($request->validated('invoice_id')),
            $request->validated(),
            $request->user(),
            $tenant,
        );
    }

    public function allocate(StorePaymentRequest $request, Invoice $invoice, TenantContext $tenant): JsonResponse
    {
        $this->authorize('view', $invoice);

        $data = $request->validated();
        $data['invoice_id'] = $invoice->id;

        return $this->recordAgainstInvoice($invoice, $data, $request->user(), $tenant);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function recordAgainstInvoice(Invoice $invoice, array $data, $user, TenantContext $tenant): JsonResponse
    {
        $this->authorize('view', $invoice);
        $this->authorize('create', Payment::class);

        try {
            $payment = $this->payments->record($tenant->get(), $invoice, $data, $user)
                ->load(['customer', 'invoice']);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return (new PaymentResource($payment))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(201);
    }
}
