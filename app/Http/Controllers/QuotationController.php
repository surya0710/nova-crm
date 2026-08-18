<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendQuotationMailRequest;
use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Http\Requests\UpdateQuotationStatusRequest;
use App\Mail\QuotationMail;
use App\Models\Customer;
use App\Models\Quotation;
use App\Services\ClientEmailCc;
use App\Services\CommercialFormData;
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

    public function create(TenantContext $tenant): View
    {
        $organization = $tenant->get();

        return view('quotations.create', [
            'quotation' => new Quotation([
                'status' => 'draft',
                'issue_date' => now()->toDateString(),
                'valid_until' => now()->addDays(30)->toDateString(),
                'currency' => $organization?->currency ?? 'USD',
                'pricing_mode' => 'exclusive',
                'tax_treatment' => 'standard',
                'shipping_amount' => 0,
            ]),
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
        $quotation->load(['customer', 'opportunity', 'creator', 'items.product', 'attachments.uploader', 'invoice']);

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
            $invoice = $this->conversionService->convert($quotation, $request->user());
        } catch (ValidationException $e) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'invoice-created-from-quotation');
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
        $to = $request->validated('email');
        $wasDraft = $quotation->status === 'draft';
        $cc = ClientEmailCc::merge($request->user(), $to, $request->input('cc'));

        try {
            $this->organizationMailer->send(
                $organization,
                $to,
                new QuotationMail(
                    $quotation,
                    $organization,
                    $request->validated('message'),
                    $request->file('attachments', []),
                ),
                $cc,
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
                ->with('status', 'quotation-email-sent')
                ->withErrors($e->errors());
        }

        if (! $wasDraft) {
            $this->quotationService->recordEmailSent($quotation, $request->user(), $to);
        }

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('status', 'quotation-email-sent');
    }

    public function pdf(Quotation $quotation): Response|StreamedResponse
    {
        $this->authorize('view', $quotation);

        return $this->pdfService->download($quotation);
    }
}
