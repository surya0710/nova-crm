<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendPaymentMailRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Mail\PaymentMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\OrganizationMailer;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(protected OrganizationMailer $organizationMailer)
    {
        $this->authorizeResource(Payment::class, 'payment');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
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
        $invoice = null;

        if ($invoiceId = $request->integer('invoice')) {
            $invoice = Invoice::query()->with('customer')->findOrFail($invoiceId);
            $this->authorize('view', $invoice);
        }

        $openInvoices = Invoice::query()
            ->with('customer')
            ->whereNotIn('status', ['paid', 'cancelled'])
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

        if ($invoice->status === 'cancelled') {
            return back()->withErrors(['invoice_id' => __('Cannot record payment on a cancelled invoice.')]);
        }

        $balanceDue = $invoice->balance_due;
        if ((float) $validated['amount'] > $balanceDue) {
            return back()->withErrors(['amount' => __('Payment exceeds balance due (:balance).', [
                'balance' => number_format($balanceDue, 2).' '.$invoice->currency,
            ])])->withInput();
        }

        $payment = DB::transaction(function () use ($organization, $validated, $invoice, $request) {
            $payment = Payment::query()->create([
                'number' => Payment::generateNumber($organization),
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => $validated['amount'],
                'currency' => $invoice->currency,
                'payment_date' => $validated['payment_date'],
                'method' => $validated['method'],
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'recorded_by' => $request->user()->id,
            ]);

            $invoice->recalculateAmountPaid();

            return $payment;
        });

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

        if (! $this->organizationMailer->isConfigured($organization)) {
            return redirect()
                ->route('payments.show', $payment)
                ->with('error', __('Configure organization email in Settings → Email before sending.'));
        }

        $payment->load(['invoice', 'customer', 'recorder']);

        try {
            $this->organizationMailer->send(
                $organization,
                $request->validated('email'),
                new PaymentMail(
                    $payment,
                    $organization,
                    $request->validated('message'),
                    $request->file('attachments', []),
                ),
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('payments.show', $payment)
                ->with('error', __('Failed to send email: :message', ['message' => $e->getMessage()]));
        }

        return redirect()
            ->route('payments.show', $payment)
            ->with('status', 'payment-email-sent');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $invoice = $payment->invoice;

        DB::transaction(function () use ($payment, $invoice) {
            $payment->delete();
            $invoice->recalculateAmountPaid();
        });

        return redirect()
            ->route('payments.index')
            ->with('status', 'payment-deleted');
    }
}
