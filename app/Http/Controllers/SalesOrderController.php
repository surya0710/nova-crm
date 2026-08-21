<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendSalesOrderMailRequest;
use App\Http\Requests\StoreSalesOrderRequest;
use App\Http\Requests\UpdateSalesOrderRequest;
use App\Http\Requests\UpdateSalesOrderStatusRequest;
use App\Mail\SalesOrderMail;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Services\CommercialFormData;
use App\Services\CrmEmailService;
use App\Services\OrganizationMailer;
use App\Services\SalesOrderConversionService;
use App\Services\SalesOrderPdfService;
use App\Services\SalesOrderService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesOrderController extends Controller
{
    public function __construct(
        protected OrganizationMailer $organizationMailer,
        protected CrmEmailService $crmEmails,
        protected SalesOrderService $salesOrderService,
        protected SalesOrderConversionService $conversionService,
        protected SalesOrderPdfService $pdfService,
        protected CommercialFormData $commercialFormData,
    ) {
        $this->authorizeResource(SalesOrder::class, 'sales_order');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $organization = $tenant->get();

        $query = SalesOrder::query()
            ->with(['customer', 'creator'])
            ->latest('order_date')
            ->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('company', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($customerId = $request->integer('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        return view('sales-orders.index', [
            'salesOrders' => $query->paginate(15)->withQueryString(),
            'organization' => $organization,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'company']),
            'filters' => $request->only(['search', 'status', 'customer_id']),
        ]);
    }

    public function create(Request $request, TenantContext $tenant): View
    {
        $organization = $tenant->get();
        $quotation = null;

        if ($quotationId = $request->integer('quotation')) {
            $quotation = Quotation::query()->with('items')->findOrFail($quotationId);
            $this->authorize('view', $quotation);
        }

        $salesOrder = new SalesOrder([
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'expected_delivery_date' => now()->addDays(14)->toDateString(),
            'currency' => $organization?->currency ?? 'USD',
            'pricing_mode' => 'exclusive',
            'tax_treatment' => 'standard',
            'shipping_amount' => 0,
        ]);

        if ($quotation) {
            $salesOrder->fill([
                'customer_id' => $quotation->customer_id,
                'quotation_id' => $quotation->id,
                'opportunity_id' => $quotation->opportunity_id,
                'title' => $quotation->title,
                'currency' => $quotation->currency,
                'notes' => $quotation->notes,
                'terms' => $quotation->terms,
                'pricing_mode' => $quotation->pricing_mode,
                'tax_treatment' => $quotation->tax_treatment,
                'place_of_supply' => $quotation->place_of_supply,
                'shipping_amount' => $quotation->shipping_amount,
            ]);
            $salesOrder->setRelation('items', $quotation->items);
        }

        return view('sales-orders.create', [
            'salesOrder' => $salesOrder,
            'sourceQuotation' => $quotation,
            ...$this->commercialFormData->for($tenant),
        ]);
    }

    public function store(StoreSalesOrderRequest $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();

        $salesOrder = $this->salesOrderService->create(
            $organization,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('sales-orders.show', $salesOrder)
            ->with('status', 'sales-order-created');
    }

    public function show(SalesOrder $salesOrder, TenantContext $tenant): View
    {
        $salesOrder->load([
            'customer',
            'quotation',
            'opportunity',
            'creator',
            'items.product',
            'attachments.uploader',
            'invoice',
        ]);

        return view('sales-orders.show', [
            'salesOrder' => $salesOrder,
            'organization' => $tenant->get(),
        ]);
    }

    public function edit(SalesOrder $salesOrder, TenantContext $tenant): View
    {
        $salesOrder->load('items');

        return view('sales-orders.edit', [
            'salesOrder' => $salesOrder,
            ...$this->commercialFormData->for($tenant),
        ]);
    }

    public function update(UpdateSalesOrderRequest $request, SalesOrder $salesOrder): RedirectResponse
    {
        try {
            $this->salesOrderService->update(
                $salesOrder,
                $request->validated(),
                $request->user(),
            );
        } catch (ValidationException $e) {
            return redirect()
                ->route('sales-orders.edit', $salesOrder)
                ->withInput()
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('sales-orders.show', $salesOrder)
            ->with('status', 'sales-order-updated');
    }

    public function destroy(SalesOrder $salesOrder): RedirectResponse
    {
        try {
            $this->salesOrderService->delete($salesOrder, auth()->user());
        } catch (ValidationException $e) {
            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('sales-orders.index')
            ->with('status', 'sales-order-deleted');
    }

    public function updateStatus(UpdateSalesOrderStatusRequest $request, SalesOrder $salesOrder): RedirectResponse
    {
        try {
            $this->salesOrderService->updateStatus(
                $salesOrder,
                $request->validated('status'),
                $request->user(),
            );
        } catch (ValidationException $e) {
            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('sales-orders.show', $salesOrder)
            ->with('status', 'sales-order-status-updated');
    }

    public function convert(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('convert', $salesOrder);

        try {
            $invoice = $this->conversionService->convert($salesOrder, $request->user());
        } catch (ValidationException $e) {
            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'invoice-created-from-sales-order');
    }

    public function sendMail(SendSalesOrderMailRequest $request, SalesOrder $salesOrder, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();

        if (! $organization) {
            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->with('error', __('Organization not found.'));
        }

        $salesOrder->load(['customer', 'creator', 'items.product']);

        try {
            $message = $this->crmEmails->send(
                $organization,
                $request->user(),
                $salesOrder,
                $request->validated(),
                new SalesOrderMail(
                    $salesOrder,
                    $organization,
                    $request->validated('message'),
                    $request->file('attachments', []) ?? [],
                ),
                $request->file('attachments', []) ?? [],
                ccSender: true,
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->with('error', __('Failed to send email: :message', ['message' => $e->getMessage()]));
        }

        $this->salesOrderService->recordEmailSent($salesOrder, $request->user(), $request->validated('email'));

        return redirect()
            ->route('sales-orders.show', $salesOrder)
            ->with('status', $message->flashKey('sales-order-email-sent'));
    }

    public function pdf(SalesOrder $salesOrder): Response|StreamedResponse
    {
        $this->authorize('view', $salesOrder);

        return $this->pdfService->download($salesOrder);
    }
}
