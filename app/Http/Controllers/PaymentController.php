<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendPaymentMailRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Mail\PaymentMail;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\CrmEmailService;
use App\Services\OrganizationMailer;
use App\Services\PaymentPdfService;
use App\Services\PaymentService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function __construct(
        protected OrganizationMailer $organizationMailer,
        protected CrmEmailService $crmEmails,
        protected PaymentService $paymentService,
        protected PaymentPdfService $pdfService,
    ) {
        $this->authorizeResource(Payment::class, 'payment', [
            'except' => ['create', 'store'],
        ]);
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $this->authorize('viewAny', Payment::class);

        $query = Payment::query()
            ->with(['invoice', 'customer', 'recorder'])
            ->latest('payment_date')
            ->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('invoice', fn ($iq) => $iq->where('number', 'like', "%{$search}%"))
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('company', 'like', "%{$search}%");
                    });
            });
        }

        if ($method = $request->string('method')->toString()) {
            $query->where('method', $method);
        }

        if ($invoiceId = $request->integer('invoice_id')) {
            $query->where('invoice_id', $invoiceId);
        }

        return view('payments.index', [
            'payments' => $query->paginate(15)->withQueryString(),
            'organization' => $tenant->get(),
            'filters' => $request->only(['search', 'method', 'invoice_id']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Payment::class);

        $invoice = null;

        if ($invoiceId = $request->integer('invoice')) {
            $invoice = Invoice::query()->with('customer')->findOrFail($invoiceId);
            $this->authorize('view', $invoice);
        }

        $openInvoices = Invoice::query()
            ->with('customer')
            ->whereIn('status', config('payments.payable_invoice_statuses', []))
            ->where('total', '>', 0)
            ->orderByDesc('issue_date')
            ->get();

        return view('payments.create', [
            'payment' => new Payment([
                'payment_date' => now()->toDateString(),
                'method' => 'bank_transfer',
                'invoice_id' => $invoice?->id,
                'amount' => $invoice?->balance_due,
            ]),
            'invoice' => $invoice,
            'openInvoices' => $openInvoices,
        ]);
    }

    public function store(StorePaymentRequest $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();
        $validated = $request->validated();

        $invoice = Invoice::query()->findOrFail($validated['invoice_id']);
        $this->authorize('view', $invoice);

        $payment = $this->paymentService->record(
            $organization,
            $invoice,
            $validated,
            $request->user(),
        );

        return redirect()
            ->route('payments.show', $payment)
            ->with('status', 'payment-recorded');
    }

    public function show(Payment $payment, TenantContext $tenant): View
    {
        $payment->load(['invoice', 'customer', 'recorder']);

        return view('payments.show', [
            'payment' => $payment,
            'organization' => $tenant->get(),
        ]);
    }

    public function sendMail(SendPaymentMailRequest $request, Payment $payment, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();

        if (! $organization) {
            return redirect()
                ->route('payments.show', $payment)
                ->with('error', __('Organization not found.'));
        }

        $payment->load(['invoice', 'customer', 'recorder']);

        try {
            $message = $this->crmEmails->send(
                $organization,
                $request->user(),
                $payment,
                $request->validated(),
                new PaymentMail(
                    $payment,
                    $organization,
                    $request->validated('message'),
                    $request->file('attachments', []) ?? [],
                    $this->pdfService->output($payment),
                ),
                $request->file('attachments', []) ?? [],
                ccSender: true,
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('payments.show', $payment)
                ->with('error', __('Failed to send email: :message', ['message' => $e->getMessage()]));
        }

        return redirect()
            ->route('payments.show', $payment)
            ->with('status', $message->flashKey('payment-email-sent'));
    }

    public function pdf(Payment $payment): Response|StreamedResponse
    {
        $this->authorize('view', $payment);

        return $this->pdfService->download($payment);
    }
}
