<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdjustmentNoteResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\QuotationResource;
use App\Http\Resources\SalesOrderResource;
use App\Models\AdjustmentNote;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Services\CommercialAutomationService;
use App\Services\CommercialPortalService;
use App\Services\PaymentService;
use App\Services\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalCommercialApiController extends Controller
{
    public function __construct(
        protected CommercialPortalService $portal,
        protected CommercialAutomationService $automation,
        protected QuotationService $quotations,
        protected PaymentService $payments,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $summary = $this->portal->summary($request->user('client'));
        unset($summary['customer'], $summary['ledger']);

        return response()->json(['data' => $summary]);
    }

    public function quotations(Request $request): JsonResponse
    {
        $quotations = $this->portal->visibleQuotations($request->user('client'))->load(['items', 'customer']);

        return QuotationResource::collection($quotations)->response();
    }

    public function showQuotation(Request $request, Organization $organization, Quotation $quotation): QuotationResource
    {
        $quotation = $this->portal->scopedFind($request->user('client'), Quotation::class, $quotation->id);
        $this->portal->recordActivity($quotation, 'portal_viewed', $request->user('client'));

        return new QuotationResource($quotation->load(['items', 'customer']));
    }

    public function acceptQuotation(Request $request, Organization $organization, Quotation $quotation): QuotationResource
    {
        $quotation = $this->portal->scopedFind($request->user('client'), Quotation::class, $quotation->id);
        $actor = $quotation->creator;
        abort_unless($actor, 422);
        abort_unless(in_array($quotation->status, ['sent'], true), 403);

        $quotation = $this->quotations->updateStatus($quotation, 'accepted', $actor);
        $this->portal->recordActivity($quotation, 'portal_accepted', $request->user('client'));

        return new QuotationResource($quotation->load(['items', 'customer']));
    }

    public function rejectQuotation(Request $request, Organization $organization, Quotation $quotation): QuotationResource
    {
        $quotation = $this->portal->scopedFind($request->user('client'), Quotation::class, $quotation->id);
        $actor = $quotation->creator;
        abort_unless($actor, 422);
        abort_unless(in_array($quotation->status, ['sent'], true), 403);

        $quotation = $this->quotations->updateStatus($quotation, 'rejected', $actor);
        $this->portal->recordActivity($quotation, 'portal_rejected', $request->user('client'));

        return new QuotationResource($quotation->load(['items', 'customer']));
    }

    public function salesOrders(Request $request): JsonResponse
    {
        $orders = $this->portal->visibleSalesOrders($request->user('client'))->load(['items', 'customer']);

        return SalesOrderResource::collection($orders)->response();
    }

    public function showSalesOrder(Request $request, Organization $organization, SalesOrder $salesOrder): SalesOrderResource
    {
        $salesOrder = $this->portal->scopedFind($request->user('client'), SalesOrder::class, $salesOrder->id);
        $this->portal->recordActivity($salesOrder, 'portal_viewed', $request->user('client'));

        return new SalesOrderResource($salesOrder->load(['items', 'customer']));
    }

    public function invoices(Request $request): JsonResponse
    {
        $invoices = $this->portal->visibleInvoices($request->user('client'))->load(['items', 'customer']);

        return InvoiceResource::collection($invoices)->response();
    }

    public function showInvoice(Request $request, Organization $organization, Invoice $invoice): InvoiceResource
    {
        $invoice = $this->portal->scopedFind($request->user('client'), Invoice::class, $invoice->id);
        $this->portal->recordActivity($invoice, 'portal_viewed', $request->user('client'));

        return new InvoiceResource($invoice->load(['items', 'customer']));
    }

    public function payInvoice(Request $request, Organization $organization, Invoice $invoice): JsonResponse
    {
        abort_unless($this->automation->gatewayConfigured($organization), 404);

        $invoice = $this->portal->scopedFind($request->user('client'), Invoice::class, $invoice->id);
        $amount = $this->portal->outstandingFor($invoice);
        abort_unless($amount > 0 && $invoice->canAcceptPayment(), 422);

        $actor = $invoice->creator ?? $organization->users()->first();
        abort_unless($actor, 422);

        $payment = $this->payments->record($organization, $invoice, [
            'amount' => $amount,
            'payment_date' => now()->toDateString(),
            'method' => 'online',
            'reference' => 'portal-'.$this->automation->paymentGateway($organization).'-'.now()->timestamp,
            'notes' => __('Paid via customer portal'),
        ], $actor);

        $this->portal->recordActivity($invoice, 'portal_paid', $request->user('client'), [
            'payment_id' => $payment->id,
            'amount' => (float) $payment->amount,
        ]);

        return (new PaymentResource($payment->load(['invoice', 'customer'])))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(201);
    }

    public function payments(Request $request): JsonResponse
    {
        $payments = $this->portal->visiblePayments($request->user('client'))->load(['invoice', 'customer']);

        return PaymentResource::collection($payments)->response();
    }

    public function notes(Request $request): JsonResponse
    {
        $notes = $this->portal->visibleNotes($request->user('client'))->load(['items', 'customer']);

        return AdjustmentNoteResource::collection($notes)->response();
    }

    public function ledger(Request $request): JsonResponse
    {
        $customer = $this->portal->requireCustomer($request->user('client'));
        $statement = app(\App\Services\RevenueService::class)->customerStatement($customer);
        unset($statement['invoices'], $statement['payments'], $statement['notes']);

        return response()->json(['data' => $statement]);
    }
}
