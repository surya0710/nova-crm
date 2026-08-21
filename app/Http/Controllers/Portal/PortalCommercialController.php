<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AdjustmentNote;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Services\AdjustmentNotePdfService;
use App\Services\CommercialAutomationService;
use App\Services\CommercialPortalService;
use App\Services\InvoicePdfService;
use App\Services\PaymentService;
use App\Services\QuotationPdfService;
use App\Services\QuotationService;
use App\Services\SalesOrderPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalCommercialController extends Controller
{
    public function __construct(
        protected CommercialPortalService $portal,
        protected CommercialAutomationService $automation,
        protected QuotationService $quotations,
        protected PaymentService $payments,
        protected InvoicePdfService $invoicePdf,
        protected QuotationPdfService $quotationPdf,
        protected SalesOrderPdfService $salesOrderPdf,
        protected AdjustmentNotePdfService $notePdf,
    ) {}

    public function overview(Request $request, Organization $organization): View
    {
        return view('portal.commercial.overview', [
            'summary' => $this->portal->summary($request->user('client')),
            'gatewayConfigured' => $this->automation->gatewayConfigured($organization),
        ]);
    }

    public function quotations(Request $request): View
    {
        return view('portal.commercial.quotations', [
            'quotations' => $this->portal->visibleQuotations($request->user('client')),
        ]);
    }

    public function showQuotation(Request $request, Organization $organization, Quotation $quotation): View
    {
        $quotation = $this->portal->scopedFind($request->user('client'), Quotation::class, $quotation->id);
        $this->portal->recordActivity($quotation, 'portal_viewed', $request->user('client'));
        $quotation->load(['items', 'customer']);

        return view('portal.commercial.quotation-show', compact('quotation'));
    }

    public function acceptQuotation(Request $request, Organization $organization, Quotation $quotation): RedirectResponse
    {
        $quotation = $this->portal->scopedFind($request->user('client'), Quotation::class, $quotation->id);
        $actor = $quotation->creator;
        abort_unless($actor, 422);
        abort_unless(in_array($quotation->status, ['sent'], true), 403);

        $this->quotations->updateStatus($quotation, 'accepted', $actor);
        $this->portal->recordActivity($quotation, 'portal_accepted', $request->user('client'));

        return redirect()
            ->route('portal.commercial.quotations.show', [$organization, $quotation])
            ->with('status', __('Quotation accepted.'));
    }

    public function rejectQuotation(Request $request, Organization $organization, Quotation $quotation): RedirectResponse
    {
        $quotation = $this->portal->scopedFind($request->user('client'), Quotation::class, $quotation->id);
        $actor = $quotation->creator;
        abort_unless($actor, 422);
        abort_unless(in_array($quotation->status, ['sent'], true), 403);

        $this->quotations->updateStatus($quotation, 'rejected', $actor);
        $this->portal->recordActivity($quotation, 'portal_rejected', $request->user('client'));

        return redirect()
            ->route('portal.commercial.quotations.show', [$organization, $quotation])
            ->with('status', __('Quotation rejected.'));
    }

    public function quotationPdf(Request $request, Organization $organization, Quotation $quotation): Response|StreamedResponse
    {
        $quotation = $this->portal->scopedFind($request->user('client'), Quotation::class, $quotation->id);

        return $this->quotationPdf->download($quotation);
    }

    public function salesOrders(Request $request): View
    {
        return view('portal.commercial.sales-orders', [
            'salesOrders' => $this->portal->visibleSalesOrders($request->user('client')),
        ]);
    }

    public function showSalesOrder(Request $request, Organization $organization, SalesOrder $salesOrder): View
    {
        $salesOrder = $this->portal->scopedFind($request->user('client'), SalesOrder::class, $salesOrder->id);
        $this->portal->recordActivity($salesOrder, 'portal_viewed', $request->user('client'));
        $salesOrder->load(['items', 'customer']);

        return view('portal.commercial.sales-order-show', compact('salesOrder'));
    }

    public function salesOrderPdf(Request $request, Organization $organization, SalesOrder $salesOrder): Response|StreamedResponse
    {
        $salesOrder = $this->portal->scopedFind($request->user('client'), SalesOrder::class, $salesOrder->id);

        return $this->salesOrderPdf->download($salesOrder);
    }

    public function invoices(Request $request): View
    {
        return view('portal.commercial.invoices', [
            'invoices' => $this->portal->visibleInvoices($request->user('client')),
        ]);
    }

    public function showInvoice(Request $request, Organization $organization, Invoice $invoice): View
    {
        $invoice = $this->portal->scopedFind($request->user('client'), Invoice::class, $invoice->id);
        $this->portal->recordActivity($invoice, 'portal_viewed', $request->user('client'));
        $invoice->load(['items', 'customer', 'payments']);

        return view('portal.commercial.invoice-show', [
            'invoice' => $invoice,
            'outstanding' => $this->portal->outstandingFor($invoice),
            'gatewayConfigured' => $this->automation->gatewayConfigured($organization),
        ]);
    }

    public function invoicePdf(Request $request, Organization $organization, Invoice $invoice): Response|StreamedResponse
    {
        $invoice = $this->portal->scopedFind($request->user('client'), Invoice::class, $invoice->id);

        return $this->invoicePdf->download($invoice);
    }

    public function payInvoice(Request $request, Organization $organization, Invoice $invoice): RedirectResponse
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

        return redirect()
            ->route('portal.commercial.invoices.show', [$organization, $invoice])
            ->with('status', __('Payment recorded.'));
    }

    public function payments(Request $request): View
    {
        return view('portal.commercial.payments', [
            'payments' => $this->portal->visiblePayments($request->user('client')),
        ]);
    }

    public function notes(Request $request): View
    {
        return view('portal.commercial.notes', [
            'notes' => $this->portal->visibleNotes($request->user('client')),
        ]);
    }

    public function notePdf(Request $request, Organization $organization, AdjustmentNote $adjustmentNote): Response|StreamedResponse
    {
        $note = $this->portal->scopedFind($request->user('client'), AdjustmentNote::class, $adjustmentNote->id);

        return $this->notePdf->download($note);
    }
}
