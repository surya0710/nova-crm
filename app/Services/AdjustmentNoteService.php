<?php

namespace App\Services;

use App\Events\AdjustmentNoteApplied;
use App\Events\AdjustmentNoteCreated;
use App\Models\AdjustmentNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use App\Services\Tax\CommercialDocumentFields;
use App\Services\Tax\TaxDeterminationService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustmentNoteService
{
    public function __construct(
        protected AdjustmentNoteCalculationService $calculator,
        protected TaxDeterminationService $taxDetermination,
        protected AuditLogger $auditLogger,
        protected CommercialPartySnapshot $partySnapshot,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, string $type, array $data, User $user): AdjustmentNote
    {
        if (($data['status'] ?? 'draft') !== 'draft') {
            throw ValidationException::withMessages([
                'status' => [__('New notes must be created as draft.')],
            ]);
        }

        $this->assertType($type);
        $this->assertInvoiceBelongs($data, $organization, $type);

        $context = $this->taxContext($organization, $data);
        $totals = $this->calculator->calculateTotals($data['items'], $context);

        return DB::transaction(function () use ($organization, $type, $data, $totals, $user) {
            $note = AdjustmentNote::query()->create([
                'organization_id' => $organization->id,
                'number' => AdjustmentNote::generateNumber($organization, $type),
                'type' => $type,
                'customer_id' => $data['customer_id'],
                'invoice_id' => $data['invoice_id'] ?? null,
                'opportunity_id' => $data['opportunity_id'] ?? null,
                'title' => $data['title'] ?? null,
                'status' => 'draft',
                'reason' => $data['reason'] ?? null,
                'reason_detail' => $data['reason_detail'] ?? null,
                'issue_date' => $data['issue_date'],
                'currency' => $data['currency'],
                ...CommercialDocumentFields::documentFromTotals($totals),
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                ...$this->snapshotsFor($data, $organization),
                'created_by' => $user->id,
            ]);

            $this->syncItems($note, $totals['items']);
            $note = $note->fresh(['items']);
            event(AdjustmentNoteCreated::forModel($note, ['actor_id' => $user->id, 'type' => $type]));

            return $note;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AdjustmentNote $note, array $data, User $user): AdjustmentNote
    {
        $this->assertEditable($note);
        $this->assertInvoiceBelongs($data, $note->organization, $note->type);

        $context = $this->taxContext($note->organization, $data);
        $totals = $this->calculator->calculateTotals($data['items'], $context);

        DB::transaction(function () use ($note, $data, $totals) {
            $note->update([
                'customer_id' => $data['customer_id'],
                'invoice_id' => $data['invoice_id'] ?? null,
                'opportunity_id' => $data['opportunity_id'] ?? null,
                'title' => $data['title'] ?? null,
                'reason' => $data['reason'] ?? null,
                'reason_detail' => $data['reason_detail'] ?? null,
                'issue_date' => $data['issue_date'],
                'currency' => $data['currency'],
                ...CommercialDocumentFields::documentFromTotals($totals),
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                ...$this->snapshotsFor($data, $note->organization),
            ]);

            $note->items()->delete();
            $this->syncItems($note, $totals['items']);
        });

        return $note->fresh(['items']);
    }

    public function delete(AdjustmentNote $note): void
    {
        $this->assertDeletable($note);
        $note->delete();
    }

    public function issue(AdjustmentNote $note, User $user): AdjustmentNote
    {
        if (! $note->canIssue()) {
            throw ValidationException::withMessages([
                'status' => [__('Only draft notes can be issued.')],
            ]);
        }

        $note->loadMissing('items');

        if ($note->items->isEmpty() || (float) $note->total <= 0) {
            throw ValidationException::withMessages([
                'status' => [__('Cannot issue a note with no value.')],
            ]);
        }

        return DB::transaction(function () use ($note, $user) {
            $note->updateQuietly([
                'status' => 'issued',
                'issue_date' => now()->toDateString(),
            ]);

            $this->auditLogger->log($note, 'issued', [
                'from' => 'draft',
                'to' => 'issued',
            ], $user);

            return $note->fresh();
        });
    }

    public function apply(AdjustmentNote $note, User $user): AdjustmentNote
    {
        if (! $note->canApply()) {
            throw ValidationException::withMessages([
                'status' => [__('This note cannot be applied in its current status.')],
            ]);
        }

        $invoice = $this->invoiceForOrganization($note->invoice_id, $note->organization);

        if (! $invoice) {
            throw ValidationException::withMessages([
                'invoice_id' => [__('A matching invoice is required before applying this note.')],
            ]);
        }

        if (in_array($invoice->status, ['draft', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'invoice_id' => [__('Notes can only be applied to issued invoices.')],
            ]);
        }

        $invoiceTotal = (float) $invoice->total;
        $invoicePaid = (float) $invoice->amount_paid;

        return DB::transaction(function () use ($note, $invoice, $user, $invoiceTotal, $invoicePaid) {
            $locked = Invoice::query()
                ->withoutGlobalScope(OrganizationScope::class)
                ->where('organization_id', $note->organization_id)
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();
            $historicalTotal = (float) $locked->total;
            $historicalPaid = (float) $locked->amount_paid;

            $note->updateQuietly([
                'status' => 'applied',
                'applied_amount' => (float) $note->total,
                'applied_at' => now(),
            ]);

            $this->auditLogger->log($note, 'applied', [
                'from' => 'issued',
                'to' => 'applied',
                'invoice_id' => $locked->id,
                'invoice_number' => $locked->number,
                'applied_amount' => (float) $note->total,
                'invoice_total_unchanged' => $historicalTotal,
                'invoice_amount_paid_unchanged' => $historicalPaid,
            ], $user);

            $this->auditLogger->log($locked, 'adjustment_applied', [
                'note_id' => $note->id,
                'note_number' => $note->number,
                'note_type' => $note->type,
                'applied_amount' => (float) $note->total,
                'invoice_total' => $historicalTotal,
            ], $user);

            event(AdjustmentNoteApplied::forModel($note->fresh(), [
                'actor_id' => $user->id,
                'invoice_id' => $locked->id,
            ]));

            $locked->refresh();

            if ((float) $locked->total !== $invoiceTotal || (float) $locked->amount_paid !== $invoicePaid) {
                throw ValidationException::withMessages([
                    'invoice_id' => [__('Applying a note must not change stored invoice values.')],
                ]);
            }

            return $note->fresh();
        });
    }

    public function cancel(AdjustmentNote $note, User $user): AdjustmentNote
    {
        if (! $note->canCancel()) {
            throw ValidationException::withMessages([
                'status' => [__('This note cannot be cancelled.')],
            ]);
        }

        return DB::transaction(function () use ($note, $user) {
            $from = $note->status;
            $note->updateQuietly(['status' => 'cancelled']);
            $this->auditLogger->log($note, 'cancelled', [
                'from' => $from,
                'to' => 'cancelled',
            ], $user);

            return $note->fresh();
        });
    }

    public function recordEmailSent(AdjustmentNote $note, User $user, string $to): void
    {
        $this->auditLogger->log($note, 'sent', ['to' => $to], $user);
    }

    public function assertEditable(AdjustmentNote $note): void
    {
        if (! $note->isEditable()) {
            throw ValidationException::withMessages([
                'adjustment_note' => [__('This note cannot be edited in its current status.')],
            ]);
        }
    }

    public function assertDeletable(AdjustmentNote $note): void
    {
        if (! $note->isDeletable()) {
            throw ValidationException::withMessages([
                'adjustment_note' => [__('This note cannot be deleted in its current status.')],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertInvoiceBelongs(array $data, Organization $organization, string $type): void
    {
        if (empty($data['invoice_id'])) {
            return;
        }

        $invoice = $this->invoiceForOrganization($data['invoice_id'] ?? null, $organization);

        if (! $invoice) {
            throw ValidationException::withMessages([
                'invoice_id' => [__('The selected invoice is invalid.')],
            ]);
        }

        if ((int) $invoice->customer_id !== (int) $data['customer_id']) {
            throw ValidationException::withMessages([
                'invoice_id' => [__('The invoice must belong to the same customer.')],
            ]);
        }
    }

    protected function invoiceForOrganization(mixed $invoiceId, Organization $organization): ?Invoice
    {
        if (empty($invoiceId)) {
            return null;
        }

        return Invoice::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organization->id)
            ->find($invoiceId);
    }

    protected function customerForOrganization(mixed $customerId, Organization $organization): ?Customer
    {
        if (empty($customerId)) {
            return null;
        }

        return Customer::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organization->id)
            ->find($customerId);
    }

    protected function assertType(string $type): void
    {
        if (! array_key_exists($type, config('adjustment_notes.types', []))) {
            throw ValidationException::withMessages([
                'type' => [__('Invalid note type.')],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{billing_snapshot: array<string, mixed>, shipping_snapshot: array<string, mixed>}
     */
    protected function snapshotsFor(array $data, Organization $organization): array
    {
        if (! empty($data['billing_snapshot']) || ! empty($data['shipping_snapshot'])) {
            return [
                'billing_snapshot' => $data['billing_snapshot'] ?? [],
                'shipping_snapshot' => $data['shipping_snapshot'] ?? [],
            ];
        }

        $customer = isset($data['customer_id'])
            ? $this->customerForOrganization($data['customer_id'], $organization)
            : null;

        return $this->partySnapshot->forCustomer($customer);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function taxContext(Organization $organization, array $data): array
    {
        $customer = isset($data['customer_id'])
            ? $this->customerForOrganization($data['customer_id'], $organization)
            : null;

        return array_merge(
            $this->taxDetermination->contextFor($organization, $customer, $data),
            [
                'shipping_amount' => (float) ($data['shipping_amount'] ?? 0),
            ],
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function syncItems(AdjustmentNote $note, array $items): void
    {
        foreach ($items as $item) {
            $note->items()->create(CommercialDocumentFields::itemFromCalculated($item));
        }
    }
}
