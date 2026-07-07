<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendInvoiceMailRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Mail\InvoiceMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Opportunity;
use App\Models\Product;
use App\Models\Quotation;
use App\Services\InvoiceService;
use App\Services\OrganizationMailer;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        protected OrganizationMailer $organizationMailer,
        protected InvoiceService $invoiceService,
    ) {
        $this->authorizeResource(Invoice::class, 'invoice');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $organization = $tenant->get();

        $query = Invoice::query()
            ->with(['customer', 'creator'])
            ->latest('issue_date')
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

        return view('invoices.index', [
            'invoices' => $query->paginate(15)->withQueryString(),
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

        $invoice = new Invoice([
            'status' => 'draft',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => $organization?->currency ?? 'USD',
        ]);

        if ($quotation) {
            $invoice->fill([
                'customer_id' => $quotation->customer_id,
                'quotation_id' => $quotation->id,
                'opportunity_id' => $quotation->opportunity_id,
                'title' => $quotation->title,
                'currency' => $quotation->currency,
                'notes' => $quotation->notes,
            ]);
            $invoice->setRelation('items', $quotation->items);
        }

        return view('invoices.create', [
            'invoice' => $invoice,
            'customers' => Customer::query()->orderBy('name')->get(),
            'opportunities' => Opportunity::query()->with('customer')->orderBy('title')->get(),
            'products' => Product::query()->where('status', 'active')->orderBy('name')->get(),
            'sourceQuotation' => $quotation,
        ]);
    }

    public function store(StoreInvoiceRequest $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();

        $invoice = $this->invoiceService->create(
            $organization,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'invoice-created');
    }

    public function show(Invoice $invoice, TenantContext $tenant): View
    {
        $invoice->load(['customer', 'quotation', 'opportunity', 'creator', 'items.product', 'payments.recorder', 'attachments.uploader']);

        return view('invoices.show', [
            'invoice' => $invoice,
            'organization' => $tenant->get(),
        ]);
    }

    public function edit(Invoice $invoice): View
    {
        $invoice->load('items');

        return view('invoices.edit', [
            'invoice' => $invoice,
            'customers' => Customer::query()->orderBy('name')->get(),
            'opportunities' => Opportunity::query()->with('customer')->orderBy('title')->get(),
            'products' => Product::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        try {
            $this->invoiceService->update(
                $invoice,
                $request->validated(),
                $request->user(),
            );
        } catch (ValidationException $e) {
            return redirect()
                ->route('invoices.edit', $invoice)
                ->withInput()
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'invoice-updated');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        try {
            $this->invoiceService->delete($invoice, auth()->user());
        } catch (ValidationException $e) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('invoices.index')
            ->with('status', 'invoice-deleted');
    }

    public function issue(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('issue', $invoice);

        try {
            $this->invoiceService->issue($invoice, $request->user());
        } catch (ValidationException $e) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'invoice-issued');
    }

    public function cancel(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('cancel', $invoice);

        try {
            $this->invoiceService->cancel($invoice, $request->user());
        } catch (ValidationException $e) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'invoice-cancelled');
    }

    public function sendMail(SendInvoiceMailRequest $request, Invoice $invoice, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();

        if (! $organization) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', __('Organization not found.'));
        }

        if (! $this->organizationMailer->isConfigured($organization)) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', __('Configure organization email in Settings → Email before sending.'));
        }

        $invoice->load(['customer', 'creator', 'items.product']);

        try {
            $this->organizationMailer->send(
                $organization,
                $request->validated('email'),
                new InvoiceMail(
                    $invoice,
                    $organization,
                    $request->validated('message'),
                    $request->file('attachments', []),
                ),
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', __('Failed to send email: :message', ['message' => $e->getMessage()]));
        }

        try {
            $this->invoiceService->markIssuedAfterEmail($invoice, $request->user());
        } catch (ValidationException $e) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('status', 'invoice-email-sent')
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'invoice-email-sent');
    }
}
