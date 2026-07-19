<?php

namespace App\Services;

use App\Events\PaymentReceived;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\CrmNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(Organization $organization, Invoice $invoice, array $data, User $user): Payment
    {
        $invoice->loadMissing(['payments', 'opportunity.assignee', 'creator']);

        $this->assertCanRecordPayment($invoice, (float) $data['amount']);

        return DB::transaction(function () use ($organization, $invoice, $data, $user) {
            $previousStatus = $invoice->status;

            $payment = Payment::query()->create([
                'organization_id' => $organization->id,
                'number' => Payment::generateNumber($organization),
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => $data['amount'],
                'currency' => $invoice->currency,
                'payment_date' => $data['payment_date'],
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $user->id,
            ]);

            $invoice->refresh();
            $invoice->load('payments');
            $invoice->recalculateAmountPaid();
            $invoice->refresh();

            $this->auditLogger->log($payment, 'recorded', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'amount' => (float) $payment->amount,
                'method' => $payment->method,
            ], $user);

            $this->auditLogger->log($invoice, 'payment_applied', [
                'payment_id' => $payment->id,
                'payment_number' => $payment->number,
                'amount_paid' => (float) $invoice->amount_paid,
                'balance_due' => $invoice->balance_due,
                'status' => $invoice->status,
            ], $user);

            if ($invoice->status === 'paid' && $previousStatus !== 'paid') {
                $this->auditLogger->log($invoice, 'fully_paid', [
                    'payment_id' => $payment->id,
                    'payment_number' => $payment->number,
                ], $user);
            }

            $this->notifyPaymentRecorded($invoice, $payment, $user);

            $payment = $payment->fresh(['invoice', 'customer', 'recorder']);
            event(PaymentReceived::forModel($payment, [
                'actor_id' => $user->id,
                'invoice_id' => $invoice->id,
                'amount' => (float) $payment->amount,
            ]));

            return $payment;
        });
    }

    public function assertCanRecordPayment(Invoice $invoice, float $amount): void
    {
        if (! in_array($invoice->status, config('payments.payable_invoice_statuses', []), true)) {
            throw ValidationException::withMessages([
                'invoice_id' => [__('Payments can only be recorded against issued or partially paid invoices.')],
            ]);
        }

        if ((float) $invoice->total <= 0) {
            throw ValidationException::withMessages([
                'invoice_id' => [__('Cannot record payment on an invoice with zero or negative total.')],
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => [__('Payment amount must be greater than zero.')],
            ]);
        }

        $balanceDue = $invoice->balance_due;

        if ($amount > $balanceDue) {
            throw ValidationException::withMessages([
                'amount' => [__('Payment exceeds balance due (:balance).', [
                    'balance' => number_format($balanceDue, 2).' '.$invoice->currency,
                ])],
            ]);
        }
    }

    protected function notifyPaymentRecorded(Invoice $invoice, Payment $payment, User $actor): void
    {
        $recipients = collect();

        if ($invoice->creator) {
            $recipients->push($invoice->creator);
        }

        if ($invoice->opportunity?->assignee) {
            $recipients->push($invoice->opportunity->assignee);
        }

        $recipients
            ->unique('id')
            ->reject(fn (User $user) => $user->id === $actor->id)
            ->each(function (User $recipient) use ($invoice, $payment) {
                if (! $recipient->hasPermission('invoices.view', $invoice->organization)) {
                    return;
                }

                $title = $invoice->status === 'paid'
                    ? __('Invoice fully paid')
                    : __('Payment recorded');

                $message = $invoice->status === 'paid'
                    ? __('Invoice :number has been fully paid.', ['number' => $invoice->number])
                    : __('Payment :number of :amount recorded for invoice :invoice.', [
                        'number' => $payment->number,
                        'amount' => $payment->formatted_amount,
                        'invoice' => $invoice->number,
                    ]);

                $recipient->notify(new CrmNotification(
                    title: $title,
                    message: $message,
                    actionUrl: route('invoices.show', $invoice),
                    organizationId: $invoice->organization_id,
                ));
            });
    }
}
