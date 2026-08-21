<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendAdjustmentNoteMailRequest;
use App\Http\Requests\StoreAdjustmentNoteRequest;
use App\Http\Requests\UpdateAdjustmentNoteRequest;
use App\Mail\AdjustmentNoteMail;
use App\Models\AdjustmentNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\AdjustmentNotePdfService;
use App\Services\AdjustmentNoteService;
use App\Services\CommercialFormData;
use App\Services\CrmEmailService;
use App\Services\OrganizationMailer;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdjustmentNoteController extends Controller
{
    public function __construct(
        protected OrganizationMailer $organizationMailer,
        protected CrmEmailService $crmEmails,
        protected AdjustmentNoteService $notes,
        protected AdjustmentNotePdfService $pdfService,
        protected CommercialFormData $commercialFormData,
    ) {
        $this->authorizeResource(AdjustmentNote::class, 'adjustment_note');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $type = $this->noteType($request);
        $query = AdjustmentNote::query()
            ->where('type', $type)
            ->with(['customer', 'invoice', 'creator'])
            ->latest('issue_date')
            ->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
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

        return view('adjustment-notes.index', [
            'notes' => $query->paginate(15)->withQueryString(),
            'type' => $type,
            'filters' => $request->only(['search', 'status']),
            'organization' => $tenant->get(),
        ]);
    }

    public function create(Request $request, TenantContext $tenant): View
    {
        $type = $this->noteType($request);
        $organization = $tenant->get();
        $invoice = null;

        if ($invoiceId = $request->integer('invoice')) {
            $invoice = Invoice::query()->with('items')->findOrFail($invoiceId);
            $this->authorize('view', $invoice);
        }

        $note = new AdjustmentNote([
            'type' => $type,
            'status' => 'draft',
            'issue_date' => now()->toDateString(),
            'currency' => $organization?->currency ?? 'USD',
            'pricing_mode' => 'exclusive',
            'tax_treatment' => 'standard',
            'shipping_amount' => 0,
        ]);

        if ($invoice) {
            $note->fill([
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'opportunity_id' => $invoice->opportunity_id,
                'currency' => $invoice->currency,
                'pricing_mode' => $invoice->pricing_mode,
                'tax_treatment' => $invoice->tax_treatment,
                'place_of_supply' => $invoice->place_of_supply,
                'shipping_amount' => 0,
            ]);
            $note->setRelation('items', $invoice->items);
        }

        return view('adjustment-notes.create', [
            'note' => $note,
            'type' => $type,
            'sourceInvoice' => $invoice,
            ...$this->commercialFormData->for($tenant),
        ]);
    }

    public function store(StoreAdjustmentNoteRequest $request, TenantContext $tenant): RedirectResponse
    {
        $type = $this->noteType($request);
        $note = $this->notes->create($tenant->get(), $type, $request->validated(), $request->user());

        return redirect()
            ->route($note->routePrefix().'.show', $note)
            ->with('status', 'adjustment-note-created');
    }

    public function show(AdjustmentNote $adjustmentNote, TenantContext $tenant): View
    {
        $this->assertType($adjustmentNote, request());
        $adjustmentNote->load(['customer', 'invoice', 'items.product', 'creator', 'opportunity']);

        return view('adjustment-notes.show', [
            'note' => $adjustmentNote,
            'type' => $adjustmentNote->type,
            'organization' => $tenant->get(),
        ]);
    }

    public function edit(AdjustmentNote $adjustmentNote, TenantContext $tenant): View
    {
        $this->assertType($adjustmentNote, request());
        $adjustmentNote->load('items');

        return view('adjustment-notes.edit', [
            'note' => $adjustmentNote,
            'type' => $adjustmentNote->type,
            ...$this->commercialFormData->for($tenant),
        ]);
    }

    public function update(UpdateAdjustmentNoteRequest $request, AdjustmentNote $adjustmentNote): RedirectResponse
    {
        $this->assertType($adjustmentNote, $request);
        $note = $this->notes->update($adjustmentNote, $request->validated(), $request->user());

        return redirect()
            ->route($note->routePrefix().'.show', $note)
            ->with('status', 'adjustment-note-updated');
    }

    public function destroy(AdjustmentNote $adjustmentNote): RedirectResponse
    {
        $this->assertType($adjustmentNote, request());
        $prefix = $adjustmentNote->routePrefix();
        $this->notes->delete($adjustmentNote);

        return redirect()
            ->route($prefix.'.index')
            ->with('status', 'adjustment-note-deleted');
    }

    public function issue(AdjustmentNote $adjustmentNote): RedirectResponse
    {
        $this->authorize('issue', $adjustmentNote);
        $this->notes->issue($adjustmentNote, request()->user());

        return redirect()
            ->route($adjustmentNote->routePrefix().'.show', $adjustmentNote)
            ->with('status', 'adjustment-note-issued');
    }

    public function apply(AdjustmentNote $adjustmentNote): RedirectResponse
    {
        $this->authorize('apply', $adjustmentNote);
        $this->notes->apply($adjustmentNote, request()->user());

        return redirect()
            ->route($adjustmentNote->routePrefix().'.show', $adjustmentNote)
            ->with('status', 'adjustment-note-applied');
    }

    public function cancel(AdjustmentNote $adjustmentNote): RedirectResponse
    {
        $this->authorize('cancel', $adjustmentNote);
        $this->notes->cancel($adjustmentNote, request()->user());

        return redirect()
            ->route($adjustmentNote->routePrefix().'.show', $adjustmentNote)
            ->with('status', 'adjustment-note-cancelled');
    }

    public function sendMail(SendAdjustmentNoteMailRequest $request, AdjustmentNote $adjustmentNote, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();

        if (! $organization) {
            return redirect()
                ->route($adjustmentNote->routePrefix().'.show', $adjustmentNote)
                ->with('error', __('Organization not found.'));
        }

        $adjustmentNote->load(['customer', 'creator', 'items.product']);

        try {
            $message = $this->crmEmails->send(
                $organization,
                $request->user(),
                $adjustmentNote,
                $request->validated(),
                new AdjustmentNoteMail(
                    $adjustmentNote,
                    $organization,
                    $request->validated('message'),
                    $request->file('attachments', []) ?? [],
                    $this->pdfService->output($adjustmentNote),
                ),
                $request->file('attachments', []) ?? [],
                ccSender: true,
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route($adjustmentNote->routePrefix().'.show', $adjustmentNote)
                ->with('error', __('Failed to send email: :message', ['message' => $e->getMessage()]));
        }

        $this->notes->recordEmailSent($adjustmentNote, $request->user(), $request->validated('email'));

        return redirect()
            ->route($adjustmentNote->routePrefix().'.show', $adjustmentNote)
            ->with('status', $message->flashKey('adjustment-note-email-sent'));
    }

    public function pdf(AdjustmentNote $adjustmentNote): Response|StreamedResponse
    {
        $this->authorize('view', $adjustmentNote);

        return $this->pdfService->download($adjustmentNote);
    }

    protected function noteType(Request $request): string
    {
        $name = (string) $request->route()?->getName();

        return str_starts_with($name, 'debit-notes.') ? 'debit' : 'credit';
    }

    protected function assertType(AdjustmentNote $note, Request $request): void
    {
        abort_unless($note->type === $this->noteType($request), 404);
    }
}
