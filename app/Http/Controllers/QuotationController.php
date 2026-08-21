<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendQuotationMailRequest;
use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Http\Requests\UpdateQuotationStatusRequest;
use App\Mail\QuotationMail;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Quotation;
use App\Services\CommercialFormData;
use App\Services\CrmEmailService;
use App\Services\OrganizationMailer;
use App\Services\QuotationConversionService;
use App\Services\QuotationPdfService;
use App\Services\QuotationService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuotationController extends Controller
{
    public function __construct(
        protected OrganizationMailer $organizationMailer,
        protected CrmEmailService $crmEmails,
        protected QuotationService $quotationService,
        protected QuotationConversionService $conversionService,
        protected QuotationPdfService $pdfService,
        protected CommercialFormData $commercialFormData,
    ) {
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

    public function create(Request $request, TenantContext $tenant): View
    {
        $organization = $tenant->get();
        $opportunity = null;

        if ($opportunityId = $request->integer('opportunity')) {
            $opportunity = Opportunity::query()->findOrFail($opportunityId);
            $this->authorize('view', $opportunity);
        }

        $quotation = new Quotation([
            'status' => 'draft',
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'currency' => $organization?->currency ?? 'USD',
            'pricing_mode' => 'exclusive',
            'tax_treatment' => 'standard',
            'shipping_amount' => 0,
        ]);

        if ($opportunity) {
            $quotation->fill([
                'customer_id' => $opportunity->customer_id,
                'opportunity_id' => $opportunity->id,
                'title' => $opportunity->title,
                'currency' => $opportunity->currency ?: $quotation->currency,
            ]);
        }

        return view('quotations.create', [
            'quotation' => $quotation,
            'sourceOpportunity' => $opportunity,
            ...$this->commercialFormData->for($tenant),
        ]);
    }

    public function store(StoreQuotationRequest $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();

        $quotation = $this->quotationService->create(
            $organization,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('status', 'quotation-created');
    }

    public function show(Quotation $quotation, TenantContext $tenant): View
    {
        $quotation->load(['customer', 'opportunity', 'creator', 'items.product', 'attachments.uploader', 'invoice', 'salesOrder']);

        return view('quotations.show', [
            'quotation' => $quotation,
            'organization' => $tenant->get(),
        ]);
    }

    public function edit(Quotation $quotation, TenantContext $tenant): View
    {
        $quotation->load('items');

        return view('quotations.edit', [
            'quotation' => $quotation,
            ...$this->commercialFormData->for($tenant),
        ]);
    }

    public function update(UpdateQuotationRequest $request, Quotation $quotation): RedirectResponse
    {
        try {
            $this->quotationService->update(
                $quotation,
                $request->validated(),
                $request->user(),
            );
        } catch (ValidationException $e) {
            return redirect()
                ->route('quotations.edit', $quotation)
                ->withInput()
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('status', 'quotation-updated');
    }

    public function destroy(Quotation $quotation): RedirectResponse
    {
        try {
            $this->quotationService->delete($quotation, auth()->user());
        } catch (ValidationException $e) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('quotations.index')
            ->with('status', 'quotation-deleted');
    }

    public function updateStatus(UpdateQuotationStatusRequest $request, Quotation $quotation): RedirectResponse
    {
        try {
            $this->quotationService->updateStatus(
                $quotation,
                $request->validated('status'),
                $request->user(),
            );
        } catch (ValidationException $e) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('status', 'quotation-status-updated');
    }

    public function convert(Request $request, Quotation $quotation): RedirectResponse
    {
        $this->authorize('convert', $quotation);

        try {
            $salesOrder = $this->conversionService->convert($quotation, $request->user());
        } catch (ValidationException $e) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('sales-orders.show', $salesOrder)
            ->with('status', 'sales-order-created-from-quotation');
    }

    public function sendMail(SendQuotationMailRequest $request, Quotation $quotation, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();

        if (! $organization) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', __('Organization not found.'));
        }

        $quotation->load(['customer', 'creator', 'items.product']);
        $wasDraft = $quotation->status === 'draft';

        try {
            $message = $this->crmEmails->send(
                $organization,
                $request->user(),
                $quotation,
                $request->validated(),
                new QuotationMail(
                    $quotation,
                    $organization,
                    $request->validated('message'),
                    $request->file('attachments', []) ?? [],
                ),
                $request->file('attachments', []) ?? [],
                ccSender: true,
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', __('Failed to send email: :message', ['message' => $e->getMessage()]));
        }

        try {
            $this->quotationService->markSentAfterEmail($quotation, $request->user());
        } catch (ValidationException $e) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('status', $message->flashKey('quotation-email-sent'))
                ->withErrors($e->errors());
        }

        if (! $wasDraft) {
            $this->quotationService->recordEmailSent($quotation, $request->user(), $request->validated('email'));
        }

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('status', $message->flashKey('quotation-email-sent'));
    }

    public function pdf(Quotation $quotation): Response|StreamedResponse
    {
        $this->authorize('view', $quotation);

        return $this->pdfService->download($quotation);
    }
}
