<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\CrmNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(
        protected InvoiceCalculationService $calculator,
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, array $data, User $user): Invoice
    {
        if (($data['status'] ?? 'draft') !== 'draft') {
            throw ValidationException::withMessages([
                'status' => [__('New invoices must be created as draft.')],
            ]);
        }

        $totals = $this->calculator->calculateTotals($data['items']);

        return DB::transaction(function () use ($organization, $data, $totals, $user) {
            $invoice = Invoice::query()->create([
                'number' => Invoice::generateNumber($organization),
                'customer_id' => $data['customer_id'],
                'quotation_id' => $data['quotation_id'] ?? null,
                'opportunity_id' => $data['opportunity_id'] ?? null,
                'title' => $data['title'] ?? null,
                'status' => 'draft',
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'currency' => $data['currency'],
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_total' => $totals['tax_total'],
                'total' => $totals['total'],
                'amount_paid' => 0,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->syncItems($invoice, $totals['items']);

            return $invoice->fresh(['items']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Invoice $invoice, array $data, User $user): Invoice
    {
        $invoice->loadMissing(['items', 'payments']);

        if ($invoice->isFullyEditable()) {
            return $this->updateDraft($invoice, $data, $user);
        }

        if ($invoice->isHeaderEditable()) {
            return $this->updateIssuedHeader($invoice, $data, $user);
        }

        throw ValidationException::withMessages([
            'invoice' => [__('This invoice cannot be edited in its current status (:status).', [
                'status' => $invoice->status_label,
            ])],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function updateDraft(Invoice $invoice, array $data, User $user): Invoice
    {
        $totals = $this->calculator->calculateTotals($data['items']);
        $previousTotal = (float) $invoice->total;
        $previousSubtotal = (float) $invoice->subtotal;

        DB::transaction(function () use ($invoice, $data, $totals, $user, $previousTotal, $previousSubtotal) {
            $invoice->update([
                'customer_id' => $data['customer_id'],
                'quotation_id' => $data['quotation_id'] ?? null,
                'opportunity_id' => $data['opportunity_id'] ?? null,
                'title' => $data['title'] ?? null,
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'currency' => $data['currency'],
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_total' => $totals['tax_total'],
                'total' => $totals['total'],
                'notes' => $data['notes'] ?? null,
            ]);

            $invoice->items()->delete();
            $this->syncItems($invoice, $totals['items']);

            if (
                round($previousTotal, 2) !== round((float) $totals['total'], 2)
                || round($previousSubtotal, 2) !== round($totals['subtotal'], 2)
            ) {
                $this->auditLogger->log($invoice, 'financial_recalculated', [
                    'previous_total' => $previousTotal,
                    'subtotal' => $totals['subtotal'],
                    'discount_amount' => $totals['discount_amount'],
                    'tax_total' => $totals['tax_total'],
                    'total' => $totals['total'],
                ], $user);
            }
        });

        return $invoice->fresh(['items']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function updateIssuedHeader(Invoice $invoice, array $data, User $user): Invoice
    {
        $this->assertNoFinancialChanges($invoice, $data);

        return DB::transaction(function () use ($invoice, $data, $user) {
            $invoice->update([
                'title' => $data['title'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'opportunity_id' => $data['opportunity_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->auditLogger->log($invoice, 'updated', [
                'scope' => 'header',
                'fields' => ['title', 'due_date', 'opportunity_id', 'notes'],
            ], $user);

            return $invoice->fresh(['items']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertNoFinancialChanges(Invoice $invoice, array $data): void
    {
        $errors = [];

        if ((int) $data['customer_id'] !== (int) $invoice->customer_id) {
            $errors['customer_id'] = [__('Customer cannot be changed after the invoice is issued.')];
        }

        if ($data['currency'] !== $invoice->currency) {
            $errors['currency'] = [__('Currency cannot be changed after the invoice is issued.')];
        }

        if ($data['issue_date'] !== $invoice->issue_date->toDateString()) {
            $errors['issue_date'] = [__('Issue date cannot be changed after the invoice is issued.')];
        }

        $existingItems = $invoice->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'description' => $item->description,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'tax_rate' => (float) $item->tax_rate,
            'discount_percent' => (float) $item->discount_percent,
        ])->values()->all();

        $submittedItems = collect($data['items'] ?? [])->map(fn ($item) => [
            'product_id' => $item['product_id'] ?? null,
            'description' => $item['description'],
            'quantity' => (float) $item['quantity'],
            'unit_price' => (float) $item['unit_price'],
            'tax_rate' => (float) ($item['tax_rate'] ?? 0),
            'discount_percent' => (float) ($item['discount_percent'] ?? 0),
        ])->values()->all();

        if ($existingItems !== $submittedItems) {
            $errors['items'] = [__('Line items cannot be changed after the invoice is issued.')];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function delete(Invoice $invoice, User $user): void
    {
        $this->assertDeletable($invoice);

        $invoice->delete();
    }

    public function issue(Invoice $invoice, User $user): Invoice
    {
        return DB::transaction(function () use ($invoice, $user) {
            $invoice = Invoice::query()
                ->with(['customer', 'items'])
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanIssue($invoice);

            $previousStatus = $invoice->status;

            $invoice->updateQuietly([
                'status' => 'issued',
                'issue_date' => now()->toDateString(),
            ]);

            $this->auditLogger->log($invoice, 'issued', [
                'from' => $previousStatus,
                'to' => 'issued',
                'issue_date' => $invoice->issue_date->toDateString(),
            ], $user);

            $this->notifyIssued($invoice, $user);

            return $invoice->fresh(['items', 'customer']);
        });
    }

    public function cancel(Invoice $invoice, User $user): Invoice
    {
        return DB::transaction(function () use ($invoice, $user) {
            $invoice = Invoice::query()
                ->with(['payments'])
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCanCancel($invoice);

            $previousStatus = $invoice->status;

            $invoice->updateQuietly(['status' => 'cancelled']);

            $this->auditLogger->log($invoice, 'cancelled', [
                'from' => $previousStatus,
                'to' => 'cancelled',
            ], $user);

            $this->notifyCancelled($invoice, $user);

            return $invoice->fresh();
        });
    }

    public function markIssuedAfterEmail(Invoice $invoice, User $user): Invoice
    {
        if ($invoice->status !== 'draft') {
            return $invoice;
        }

        return $this->issue($invoice, $user);
    }

    public function assertCanIssue(Invoice $invoice): void
    {
        $invoice->loadMissing(['customer', 'items']);

        if ($invoice->status !== 'draft') {
            throw ValidationException::withMessages([
                'invoice' => [__('Only draft invoices can be issued. Current status: :status.', [
                    'status' => $invoice->status_label,
                ])],
            ]);
        }

        if (! $invoice->customer_id || ! $invoice->customer) {
            throw ValidationException::withMessages([
                'invoice' => [__('Cannot issue an invoice without a customer.')],
            ]);
        }

        if ($invoice->items->isEmpty()) {
            throw ValidationException::withMessages([
                'invoice' => [__('Cannot issue an invoice with no line items.')],
            ]);
        }

        if ((float) $invoice->total <= 0) {
            throw ValidationException::withMessages([
                'invoice' => [__('Cannot issue an invoice with zero or negative total.')],
            ]);
        }
    }

    public function assertCanCancel(Invoice $invoice): void
    {
        if (! $invoice->canCancel()) {
            throw ValidationException::withMessages([
                'invoice' => [__('This invoice cannot be cancelled in its current status (:status).', [
                    'status' => $invoice->status_label,
                ])],
            ]);
        }

        if ($invoice->status === 'partially_paid' && (float) $invoice->amount_paid > 0) {
            throw ValidationException::withMessages([
                'invoice' => [__('Cannot cancel a partially paid invoice with recorded payments.')],
            ]);
        }

        if ($invoice->payments()->exists()) {
            throw ValidationException::withMessages([
                'invoice' => [__('Cannot cancel an invoice that has payments recorded.')],
            ]);
        }
    }

    public function assertDeletable(Invoice $invoice): void
    {
        if (! $invoice->isDeletable()) {
            throw ValidationException::withMessages([
                'invoice' => [__('This invoice cannot be deleted in its current status (:status).', [
                    'status' => $invoice->status_label,
                ])],
            ]);
        }

        if ($invoice->payments()->exists()) {
            throw ValidationException::withMessages([
                'invoice' => [__('Cannot delete an invoice that has payments recorded.')],
            ]);
        }

        if ((float) $invoice->amount_paid > 0) {
            throw ValidationException::withMessages([
                'invoice' => [__('Cannot delete an invoice with recorded payments.')],
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function syncItems(Invoice $invoice, array $items): void
    {
        foreach ($items as $item) {
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
    }

    protected function notifyIssued(Invoice $invoice, User $actor): void
    {
        $creator = $invoice->creator;

        if (! $creator || $creator->id === $actor->id) {
            return;
        }

        if (! $creator->hasPermission('invoices.view', $invoice->organization)) {
            return;
        }

        $creator->notify(new CrmNotification(
            title: __('Invoice issued'),
            message: __('Invoice :number has been issued.', ['number' => $invoice->number]),
            actionUrl: route('invoices.show', $invoice),
            organizationId: $invoice->organization_id,
        ));
    }

    protected function notifyCancelled(Invoice $invoice, User $actor): void
    {
        $creator = $invoice->creator;

        if (! $creator || $creator->id === $actor->id) {
            return;
        }

        if (! $creator->hasPermission('invoices.view', $invoice->organization)) {
            return;
        }

        $creator->notify(new CrmNotification(
            title: __('Invoice cancelled'),
            message: __('Invoice :number has been cancelled.', ['number' => $invoice->number]),
            actionUrl: route('invoices.show', $invoice),
            organizationId: $invoice->organization_id,
        ));
    }
}
