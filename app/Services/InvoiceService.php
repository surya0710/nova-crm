<?php

namespace App\Services;

use App\Events\CustomerFirstInvoice;
use App\Events\InvoiceCreated;
use App\Events\InvoiceIssued;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Services\Tax\CommercialDocumentFields;
use App\Services\Tax\TaxDeterminationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(
        protected InvoiceCalculationService $calculator,
        protected TaxDeterminationService $taxDetermination,
        protected AuditLogger $auditLogger,
        protected CommercialPartySnapshot $partySnapshot,
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

        $context = $this->taxContext($organization, $data);
        $totals = $this->calculator->calculateTotals($data['items'], $context);

        return $this->persistNewInvoice($organization, $data, $totals, $user);
    }

    public function createFromQuotation(Quotation $quotation, User $user): Invoice
    {
        $quotation->loadMissing(['organization', 'items']);

        $snapshots = $this->partySnapshot->fromDocument($quotation);

        $data = [
            'customer_id' => $quotation->customer_id,
            'quotation_id' => $quotation->id,
            'opportunity_id' => $quotation->opportunity_id,
            'title' => $quotation->title,
            'status' => 'draft',
            'issue_date' => now()->toDateString(),
            'due_date' => ($quotation->valid_until ?? now()->addDays(30))->toDateString(),
            'currency' => $quotation->currency,
            'notes' => $this->quotationInvoiceNotes($quotation),
            'terms' => $quotation->terms,
            ...$snapshots,
        ];
        $totals = [
            ...CommercialDocumentFields::documentFromTotals($quotation->only(CommercialDocumentFields::documentKeys())),
            'items' => $quotation->items->map(fn ($item): array => CommercialDocumentFields::itemFromCalculated(
                $item->only(CommercialDocumentFields::itemKeys())
            ))->all(),
        ];

        return $this->persistNewInvoice($quotation->organization, $data, $totals, $user);
    }

    public function createFromSalesOrder(SalesOrder $salesOrder, User $user): Invoice
    {
        $salesOrder->loadMissing(['organization', 'items', 'quotation']);

        $snapshots = $this->partySnapshot->fromDocument($salesOrder);

        $data = [
            'customer_id' => $salesOrder->customer_id,
            'quotation_id' => $salesOrder->quotation_id,
            'sales_order_id' => $salesOrder->id,
            'opportunity_id' => $salesOrder->opportunity_id,
            'title' => $salesOrder->title,
            'status' => 'draft',
            'issue_date' => now()->toDateString(),
            'due_date' => ($salesOrder->expected_delivery_date ?? now()->addDays(30))->toDateString(),
            'currency' => $salesOrder->currency,
            'notes' => $this->salesOrderInvoiceNotes($salesOrder),
            'terms' => $salesOrder->terms,
            ...$snapshots,
        ];
        $totals = [
            ...CommercialDocumentFields::documentFromTotals($salesOrder->only(CommercialDocumentFields::documentKeys())),
            'items' => $salesOrder->items->map(fn ($item): array => CommercialDocumentFields::itemFromCalculated(
                $item->only(CommercialDocumentFields::itemKeys())
            ))->all(),
        ];

        return $this->persistNewInvoice($salesOrder->organization, $data, $totals, $user);
    }

    protected function persistNewInvoice(Organization $organization, array $data, array $totals, User $user): Invoice
    {
        return DB::transaction(function () use ($organization, $data, $totals, $user) {
            $snapshots = $this->snapshotsFor($data);

            $invoice = Invoice::query()->create([
                'organization_id' => $organization->id,
                'number' => Invoice::generateNumber($organization),
                'customer_id' => $data['customer_id'],
                'quotation_id' => $data['quotation_id'] ?? null,
                'sales_order_id' => $data['sales_order_id'] ?? null,
                'opportunity_id' => $data['opportunity_id'] ?? null,
                'title' => $data['title'] ?? null,
                'status' => 'draft',
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'currency' => $data['currency'],
                ...CommercialDocumentFields::documentFromTotals($totals),
                'amount_paid' => 0,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                ...$snapshots,
                'created_by' => $user->id,
            ]);

            $this->syncItems($invoice, $totals['items']);
            $invoice = $invoice->fresh(['items']);
            event(InvoiceCreated::forModel($invoice, ['actor_id' => $user->id]));

            return $invoice;
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
        $context = $this->taxContext($invoice->organization, $data);
        $totals = $this->calculator->calculateTotals($data['items'], $context);
        $previousTotal = (float) $invoice->total;
        $previousSubtotal = (float) $invoice->subtotal;

        DB::transaction(function () use ($invoice, $data, $totals, $user, $previousTotal, $previousSubtotal) {
            $invoice->update([
                'customer_id' => $data['customer_id'],
                'quotation_id' => $data['quotation_id'] ?? $invoice->quotation_id,
                'sales_order_id' => $data['sales_order_id'] ?? $invoice->sales_order_id,
                'opportunity_id' => $data['opportunity_id'] ?? null,
                'title' => $data['title'] ?? null,
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'currency' => $data['currency'],
                ...CommercialDocumentFields::documentFromTotals($totals),
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                ...$this->snapshotsFor($data),
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
                'terms' => $data['terms'] ?? $invoice->terms,
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

            event(InvoiceIssued::forModel($invoice, ['actor_id' => $user->id]));

            $issuedCount = Invoice::query()
                ->where('customer_id', $invoice->customer_id)
                ->whereNotIn('status', ['draft', 'cancelled'])
                ->count();
            if ($issuedCount === 1) {
                event(CustomerFirstInvoice::forModel($invoice, [
                    'actor_id' => $user->id,
                    'customer_id' => $invoice->customer_id,
                ]));
            }

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

    public function recordEmailSent(Invoice $invoice, User $user, string $to): void
    {
        $this->auditLogger->log($invoice, 'sent', ['to' => $to], $user);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{billing_snapshot: array<string, mixed>, shipping_snapshot: array<string, mixed>}
     */
    protected function snapshotsFor(array $data): array
    {
        if (! empty($data['billing_snapshot']) || ! empty($data['shipping_snapshot'])) {
            return [
                'billing_snapshot' => $data['billing_snapshot'] ?? [],
                'shipping_snapshot' => $data['shipping_snapshot'] ?? [],
            ];
        }

        $customer = isset($data['customer_id'])
            ? Customer::query()->find($data['customer_id'])
            : null;

        return $this->partySnapshot->forCustomer($customer);
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
            $invoice->items()->create(CommercialDocumentFields::itemFromCalculated($item));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function taxContext(Organization $organization, array $data): array
    {
        $customer = isset($data['customer_id'])
            ? Customer::query()->find($data['customer_id'])
            : null;

        return array_merge(
            $this->taxDetermination->contextFor($organization, $customer, $data),
            [
                'shipping_amount' => (float) ($data['shipping_amount'] ?? 0),
            ],
        );
    }

    protected function quotationInvoiceNotes(Quotation $quotation): string
    {
        $reference = __('Generated from quotation :number.', ['number' => $quotation->number]);

        return $quotation->notes ? $reference."\n\n".$quotation->notes : $reference;
    }

    protected function salesOrderInvoiceNotes(SalesOrder $salesOrder): string
    {
        $reference = __('Generated from sales order :number.', ['number' => $salesOrder->number]);

        if ($salesOrder->quotation) {
            $reference .= ' '.__('Quotation :number.', ['number' => $salesOrder->quotation->number]);
        }

        return $salesOrder->notes ? $reference."\n\n".$salesOrder->notes : $reference;
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
