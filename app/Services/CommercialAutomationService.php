<?php

namespace App\Services;

use App\Events\InvoiceDueSoon;
use App\Events\InvoiceOverdue;
use App\Events\PaymentConfirmed;
use App\Events\QuotationExpiring;
use App\Mail\CommercialReminderMail;
use App\Models\CommercialReminderDispatch;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use App\Notifications\CrmNotification;
use Illuminate\Support\Facades\Log;

class CommercialAutomationService
{
    public function __construct(protected OrganizationMailer $mailer) {}

    /**
     * @return array<string, mixed>
     */
    public function settings(Organization $organization): array
    {
        $stored = $organization->settings['commercial_automation'] ?? [];

        return array_merge(config('commercial_automation.defaults', []), is_array($stored) ? $stored : []);
    }

    public function enabled(Organization $organization, string $key): bool
    {
        return (bool) ($this->settings($organization)[$key] ?? false);
    }

    public function paymentGateway(Organization $organization): string
    {
        return (string) ($this->settings($organization)['payment_gateway'] ?? '');
    }

    public function gatewayConfigured(Organization $organization): bool
    {
        $gateway = $this->paymentGateway($organization);

        return $gateway !== '' && array_key_exists($gateway, config('commercial_automation.gateways', []));
    }

    public function notifyPaymentRecorded(Payment $payment): void
    {
        $payment->loadMissing(['organization', 'customer', 'invoice', 'recorder']);
        $organization = $payment->organization;

        if (! $organization) {
            return;
        }

        $settings = $this->settings($organization);

        if (! empty($settings['payment_confirmation'])) {
            $this->emailCustomer(
                $organization,
                $payment->customer?->email,
                new CommercialReminderMail(
                    $organization,
                    'payment-confirmation',
                    __('Payment received — :number', ['number' => $payment->number]),
                    [
                        'payment' => $payment,
                        'invoice' => $payment->invoice,
                    ],
                ),
            );

            event(PaymentConfirmed::forModel($payment, [
                'invoice_id' => $payment->invoice_id,
                'amount' => (float) $payment->amount,
            ]));
        }

        if (! empty($settings['payment_receipt']) && $payment->invoice?->creator) {
            $this->notifyStaff(
                $payment->invoice->creator,
                $organization,
                __('Payment receipt :number', ['number' => $payment->number]),
                __('A payment of :amount was recorded for :invoice.', [
                    'amount' => $payment->formatted_amount,
                    'invoice' => $payment->invoice->number,
                ]),
                '/payments/'.$payment->id,
            );
        }
    }

    public function notifySalesOrder(SalesOrder $salesOrder, string $from, string $to): void
    {
        $salesOrder->loadMissing(['organization', 'creator']);
        $organization = $salesOrder->organization;

        if (! $organization || ! $this->enabled($organization, 'sales_order_notifications')) {
            return;
        }

        $recipient = $salesOrder->creator;
        if (! $recipient) {
            return;
        }

        $this->notifyStaff(
            $recipient,
            $organization,
            __('Sales order :number', ['number' => $salesOrder->number]),
            __('Status changed from :from to :to.', ['from' => $from, 'to' => $to]),
            '/sales-orders/'.$salesOrder->id,
        );
    }

    /**
     * @return array{due: int, overdue: int, quotations: int}
     */
    public function dispatchScheduledReminders(): array
    {
        $counts = ['due' => 0, 'overdue' => 0, 'quotations' => 0];

        Organization::query()->where('is_active', true)->cursor()->each(function (Organization $organization) use (&$counts) {
            $settings = $this->settings($organization);

            if (! empty($settings['invoice_due_reminders'])) {
                $counts['due'] += $this->dispatchDueSoon($organization, (int) ($settings['invoice_due_days_before'] ?? 3));
            }

            if (! empty($settings['invoice_overdue_reminders'])) {
                $counts['overdue'] += $this->dispatchOverdue($organization);
            }

            if (! empty($settings['quotation_expiry_reminders'])) {
                $counts['quotations'] += $this->dispatchQuoteExpiry($organization, (int) ($settings['quotation_expiry_days_before'] ?? 2));
            }
        });

        return $counts;
    }

    protected function dispatchDueSoon(Organization $organization, int $daysBefore): int
    {
        $target = now()->addDays(max(0, $daysBefore))->toDateString();
        $sent = 0;

        Invoice::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->whereNotIn('status', ['draft', 'cancelled', 'paid', 'overpaid'])
            ->whereDate('due_date', $target)
            ->with(['customer', 'organization'])
            ->each(function (Invoice $invoice) use ($organization, &$sent) {
                if ($invoice->effective_balance <= 0) {
                    return;
                }
                if (! $this->markDispatched($organization, $invoice, 'due_soon')) {
                    return;
                }

                event(InvoiceDueSoon::forModel($invoice, ['due_date' => $invoice->due_date?->toDateString()]));
                app(AuditLogger::class)->log($invoice, 'reminder_sent', [
                    'reminder_type' => 'due_soon',
                    'due_date' => $invoice->due_date?->toDateString(),
                ]);
                $this->emailCustomer(
                    $organization,
                    $invoice->customer?->email,
                    new CommercialReminderMail(
                        $organization,
                        'invoice-due',
                        __('Invoice :number is due soon', ['number' => $invoice->number]),
                        ['invoice' => $invoice],
                    ),
                );
                $sent++;
            });

        return $sent;
    }

    protected function dispatchOverdue(Organization $organization): int
    {
        $sent = 0;

        Invoice::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->whereNotIn('status', ['draft', 'cancelled', 'paid', 'overpaid'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->with(['customer', 'organization'])
            ->each(function (Invoice $invoice) use ($organization, &$sent) {
                if ($invoice->effective_balance <= 0) {
                    return;
                }
                if (! $this->markDispatched($organization, $invoice, 'overdue')) {
                    return;
                }

                event(InvoiceOverdue::forModel($invoice, ['due_date' => $invoice->due_date?->toDateString()]));
                app(AuditLogger::class)->log($invoice, 'reminder_sent', [
                    'reminder_type' => 'overdue',
                    'due_date' => $invoice->due_date?->toDateString(),
                ]);
                $this->emailCustomer(
                    $organization,
                    $invoice->customer?->email,
                    new CommercialReminderMail(
                        $organization,
                        'invoice-overdue',
                        __('Invoice :number is overdue', ['number' => $invoice->number]),
                        ['invoice' => $invoice],
                    ),
                );
                $sent++;
            });

        return $sent;
    }

    protected function dispatchQuoteExpiry(Organization $organization, int $daysBefore): int
    {
        $target = now()->addDays(max(0, $daysBefore))->toDateString();
        $sent = 0;

        Quotation::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->whereIn('status', ['draft', 'sent'])
            ->whereDate('valid_until', $target)
            ->with(['customer', 'organization', 'creator'])
            ->each(function (Quotation $quotation) use ($organization, &$sent) {
                if (! $this->markDispatched($organization, $quotation, 'expiring')) {
                    return;
                }

                event(QuotationExpiring::forModel($quotation, [
                    'valid_until' => $quotation->valid_until?->toDateString(),
                ]));
                app(AuditLogger::class)->log($quotation, 'reminder_sent', [
                    'reminder_type' => 'expiring',
                    'valid_until' => $quotation->valid_until?->toDateString(),
                ]);
                $this->emailCustomer(
                    $organization,
                    $quotation->customer?->email,
                    new CommercialReminderMail(
                        $organization,
                        'quotation-expiring',
                        __('Quotation :number expires soon', ['number' => $quotation->number]),
                        ['quotation' => $quotation],
                    ),
                );
                $sent++;
            });

        return $sent;
    }

    protected function markDispatched(Organization $organization, Invoice|Quotation $subject, string $type): bool
    {
        $existing = CommercialReminderDispatch::query()
            ->where('organization_id', $organization->id)
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->where('reminder_type', $type)
            ->whereDate('dispatched_on', now()->toDateString())
            ->exists();

        if ($existing) {
            return false;
        }

        CommercialReminderDispatch::query()->create([
            'organization_id' => $organization->id,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'reminder_type' => $type,
            'dispatched_on' => now()->toDateString(),
        ]);

        return true;
    }

    protected function emailCustomer(Organization $organization, ?string $email, CommercialReminderMail $mail): void
    {
        if (! $email || ! $this->mailer->isConfigured($organization)) {
            return;
        }

        try {
            $this->mailer->send($organization, $email, $mail);
        } catch (\Throwable $e) {
            Log::warning('Commercial reminder email failed', [
                'organization_id' => $organization->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function notifyStaff(User $user, Organization $organization, string $title, string $message, string $url): void
    {
        $user->notify(new CrmNotification($title, $message, $url, $organization->id));
    }
}
