<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendQuotationMailRequest;
use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Http\Requests\UpdateQuotationStatusRequest;
use App\Mail\QuotationMail;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Product;
use App\Models\Quotation;
use App\Services\OrganizationMailer;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuotationController extends Controller
{
    public function __construct(protected OrganizationMailer $organizationMailer)
    {
        $this->authorizeResource(Quotation::class, 'quotation');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $organization = $tenant->get();

        $query = Quotation::query()
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

        return view('quotations.index', [
            'quotations' => $query->paginate(15)->withQueryString(),
            'organization' => $organization,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'company']),
            'filters' => $request->only(['search', 'status', 'customer_id']),
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        $organization = $tenant->get();

        return view('quotations.create', [
            'quotation' => new Quotation([
                'status' => 'draft',
                'issue_date' => now()->toDateString(),
                'valid_until' => now()->addDays(30)->toDateString(),
                'currency' => $organization?->currency ?? 'USD',
            ]),
            'customers' => Customer::query()->orderBy('name')->get(),
            'opportunities' => Opportunity::query()->with('customer')->orderBy('title')->get(),
            'products' => Product::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreQuotationRequest $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();
        $validated = $request->validated();
        $totals = Quotation::calculateTotals($validated['items']);

        $quotation = DB::transaction(function () use ($organization, $validated, $totals, $request) {
            $quotation = Quotation::query()->create([
                'number' => Quotation::generateNumber($organization),
                'customer_id' => $validated['customer_id'],
                'opportunity_id' => $validated['opportunity_id'] ?? null,
                'title' => $validated['title'] ?? null,
                'status' => $validated['status'],
                'issue_date' => $validated['issue_date'],
                'valid_until' => $validated['valid_until'] ?? null,
                'currency' => $validated['currency'],
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_total' => $totals['tax_total'],
                'total' => $totals['total'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($totals['items'] as $item) {
                $quotation->items()->create([
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

            return $quotation;
        });

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('status', 'quotation-created');
    }

    public function show(Quotation $quotation, TenantContext $tenant): View
    {
        $quotation->load(['customer', 'opportunity', 'creator', 'items.product', 'attachments.uploader']);

        return view('quotations.show', [
            'quotation' => $quotation,
            'organization' => $tenant->get(),
        ]);
    }

    public function edit(Quotation $quotation): View
    {
        $quotation->load('items');

        return view('quotations.edit', [
            'quotation' => $quotation,
            'customers' => Customer::query()->orderBy('name')->get(),
            'opportunities' => Opportunity::query()->with('customer')->orderBy('title')->get(),
            'products' => Product::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateQuotationRequest $request, Quotation $quotation): RedirectResponse
    {
        $validated = $request->validated();
        $totals = Quotation::calculateTotals($validated['items']);

        DB::transaction(function () use ($quotation, $validated, $totals) {
            $quotation->update([
                'customer_id' => $validated['customer_id'],
                'opportunity_id' => $validated['opportunity_id'] ?? null,
                'title' => $validated['title'] ?? null,
                'status' => $validated['status'],
                'issue_date' => $validated['issue_date'],
                'valid_until' => $validated['valid_until'] ?? null,
                'currency' => $validated['currency'],
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_total' => $totals['tax_total'],
                'total' => $totals['total'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $quotation->items()->delete();

            foreach ($totals['items'] as $item) {
                $quotation->items()->create([
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
        });

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('status', 'quotation-updated');
    }

    public function destroy(Quotation $quotation): RedirectResponse
    {
        $quotation->delete();

        return redirect()
            ->route('quotations.index')
            ->with('status', 'quotation-deleted');
    }

    public function updateStatus(UpdateQuotationStatusRequest $request, Quotation $quotation): RedirectResponse
    {
        $quotation->update([
            'status' => $request->validated('status'),
        ]);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('status', 'quotation-status-updated');
    }

    public function sendMail(SendQuotationMailRequest $request, Quotation $quotation, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();

        if (! $organization) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', __('Organization not found.'));
        }

        if (! $this->organizationMailer->isConfigured($organization)) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', __('Configure organization email in Settings → Email before sending.'));
        }

        $quotation->load(['customer', 'creator', 'items.product']);

        try {
            $this->organizationMailer->send(
                $organization,
                $request->validated('email'),
                new QuotationMail(
                    $quotation,
                    $organization,
                    $request->validated('message'),
                    $request->file('attachments', []),
                ),
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', __('Failed to send email: :message', ['message' => $e->getMessage()]));
        }

        if (in_array($quotation->status, ['draft', 'expired'], true)) {
            $quotation->update(['status' => 'sent']);
        }

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('status', 'quotation-email-sent');
    }
}
