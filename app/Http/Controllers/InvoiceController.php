<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendInvoiceMailRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Requests\UpdateInvoiceStatusRequest;
use App\Mail\InvoiceMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Opportunity;
use App\Models\Product;
use App\Models\Quotation;
use App\Services\OrganizationMailer;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(protected OrganizationMailer $organizationMailer)
    {
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
        $validated = $request->validated();
        $totals = Invoice::calculateTotals($validated['items']);

        $invoice = DB::transaction(function () use ($organization, $validated, $totals, $request) {
            $invoice = Invoice::query()->create([
                'number' => Invoice::generateNumber($organization),
                'customer_id' => $validated['customer_id'],
                'quotation_id' => $validated['quotation_id'] ?? null,
                'opportunity_id' => $validated['opportunity_id'] ?? null,
                'title' => $validated['title'] ?? null,
                'status' => $validated['status'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'] ?? null,
                'currency' => $validated['currency'],
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_total' => $totals['tax_total'],
                'total' => $totals['total'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($totals['items'] as $item) {
                $invoice->items()->create([
                    'product_id' => $item['product_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'],
                    'discount_percent' => $item['discount_percent'],
                    'line_total' => $item['line_total'],
                    'sort_order' => $item['sort_order'],
                ]);
            }

            return $invoice;
        });

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
        $validated = $request->validated();
        $totals = Invoice::calculateTotals($validated['items']);

        DB::transaction(function () use ($invoice, $validated, $totals) {
            $invoice->update([
                'customer_id' => $validated['customer_id'],
                'quotation_id' => $validated['quotation_id'] ?? null,
                'opportunity_id' => $validated['opportunity_id'] ?? null,
                'title' => $validated['title'] ?? null,
                'status' => $validated['status'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'] ?? null,
                'currency' => $validated['currency'],
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_total' => $totals['tax_total'],
                'total' => $totals['total'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $invoice->syncPaymentStatus();

            $invoice->items()->delete();

            foreach ($totals['items'] as $item) {
                $invoice->items()->create([
                    'product_id' => $item['product_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'],
                    'discount_percent' => $item['discount_percent'],
                    'line_total' => $item['line_total'],
                    'sort_order' => $item['sort_order'],
                ]);
            }

            $invoice->save();
        });

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'invoice-updated');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return redirect()
            ->route('invoices.index')
            ->with('status', 'invoice-deleted');
    }

    public function updateStatus(UpdateInvoiceStatusRequest $request, Invoice $invoice): RedirectResponse
    {
        $invoice->update([
            'status' => $request->validated('status'),
        ]);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'invoice-status-updated');
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

        if (in_array($invoice->status, ['draft'], true)) {
            $invoice->update(['status' => 'sent']);
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'invoice-email-sent');
    }
}
