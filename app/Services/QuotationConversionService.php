<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\User;
use App\Notifications\CrmNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuotationConversionService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function convert(Quotation $quotation, User $user): Invoice
    {
        return DB::transaction(function () use ($quotation, $user) {
            $quotation = Quotation::query()
                ->with(['customer', 'items', 'invoice'])
                ->whereKey($quotation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingInvoice = $this->findActiveInvoice($quotation);

            if ($existingInvoice) {
                return $existingInvoice;
            }

            $this->assertEligible($quotation);

            $organization = $quotation->organization;

            $invoice = Invoice::query()->create([
                'number' => Invoice::generateNumber($organization),
                'customer_id' => $quotation->customer_id,
                'quotation_id' => $quotation->id,
                'opportunity_id' => $quotation->opportunity_id,
                'title' => $quotation->title,
                'status' => 'draft',
                'issue_date' => now()->toDateString(),
                'due_date' => ($quotation->valid_until ?? now()->addDays(30))->toDateString(),
                'currency' => $quotation->currency,
                'subtotal' => $quotation->subtotal,
                'discount_amount' => $quotation->discount_amount,
                'tax_total' => $quotation->tax_total,
                'total' => $quotation->total,
                'amount_paid' => 0,
                'notes' => $this->buildInvoiceNotes($quotation),
                'created_by' => $user->id,
            ]);

            foreach ($quotation->items as $item) {
                $invoice->items()->create([
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax_rate' => $item->tax_rate,
                    'discount_percent' => $item->discount_percent,
                    'line_total' => $item->line_total,
                    'sort_order' => $item->sort_order,
                ]);
            }

            $previousStatus = $quotation->status;

            $quotation->updateQuietly(['status' => 'converted']);

            $this->auditLogger->log($quotation, 'converted', [
                'from' => $previousStatus,
                'to' => 'converted',
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'quotation_number' => $quotation->number,
            ], $user);

            $this->auditLogger->log($invoice, 'created_from_quotation', [
                'quotation_id' => $quotation->id,
                'quotation_number' => $quotation->number,
                'invoice_number' => $invoice->number,
            ], $user);

            $this->notifyInternalUsers($quotation, $invoice, $user);

            return $invoice->fresh(['items', 'customer', 'quotation']);
        });
    }

    public function assertEligible(Quotation $quotation): void
    {
        $quotation->loadMissing(['customer', 'items']);

        if ($quotation->status === 'converted') {
            throw ValidationException::withMessages([
                'quotation' => [__('This quotation has already been converted.')],
            ]);
        }

        if ($quotation->status !== 'accepted') {
            throw ValidationException::withMessages([
                'quotation' => [__('Only accepted quotations can be converted to an invoice. Current status: :status.', [
                    'status' => $quotation->status_label,
                ])],
            ]);
        }

        if (! $quotation->customer_id || ! $quotation->customer) {
            throw ValidationException::withMessages([
                'quotation' => [__('Cannot convert a quotation without a customer.')],
            ]);
        }

        if ($quotation->items->isEmpty()) {
            throw ValidationException::withMessages([
                'quotation' => [__('Cannot convert a quotation with no line items.')],
            ]);
        }

        if ((float) $quotation->total <= 0) {
            throw ValidationException::withMessages([
                'quotation' => [__('Cannot convert a quotation with zero or negative total.')],
            ]);
        }
    }

    protected function findActiveInvoice(Quotation $quotation): ?Invoice
    {
        return Invoice::query()
            ->where('quotation_id', $quotation->id)
            ->where('status', '!=', 'cancelled')
            ->latest('id')
            ->first();
    }

    protected function buildInvoiceNotes(Quotation $quotation): ?string
    {
        $reference = __('Generated from quotation :number.', ['number' => $quotation->number]);

        if ($quotation->notes) {
            return $reference."\n\n".$quotation->notes;
        }

        return $reference;
    }

    protected function notifyInternalUsers(Quotation $quotation, Invoice $invoice, User $converter): void
    {
        $creator = $quotation->creator;

        if (! $creator || $creator->id === $converter->id) {
            return;
        }

        if (! $creator->hasPermission('invoices.view', $quotation->organization)) {
            return;
        }

        $creator->notify(new CrmNotification(
            title: __('Invoice generated'),
            message: __('Invoice :invoice was generated from quotation :quotation.', [
                'invoice' => $invoice->number,
                'quotation' => $quotation->number,
            ]),
            actionUrl: route('invoices.show', $invoice),
            organizationId: $quotation->organization_id,
        ));
    }
}
