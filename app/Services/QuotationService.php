<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\User;
use App\Services\Tax\CommercialDocumentFields;
use App\Services\Tax\TaxDeterminationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuotationService
{
    public function __construct(
        protected QuotationCalculationService $calculator,
        protected TaxDeterminationService $taxDetermination,
        protected AuditLogger $auditLogger,
        protected CommercialPartySnapshot $partySnapshot,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, array $data, User $user): Quotation
    {
        if (($data['status'] ?? 'draft') !== 'draft') {
            throw ValidationException::withMessages([
                'status' => [__('New quotations must be created as draft.')],
            ]);
        }

        $context = $this->taxContext($organization, $data);
        $totals = $this->calculator->calculateTotals($data['items'], $context);

        return DB::transaction(function () use ($organization, $data, $totals, $user) {
            $quotation = Quotation::query()->create([
                'number' => Quotation::generateNumber($organization),
                'customer_id' => $data['customer_id'],
                'opportunity_id' => $data['opportunity_id'] ?? null,
                'title' => $data['title'] ?? null,
                'status' => 'draft',
                'issue_date' => $data['issue_date'],
                'valid_until' => $data['valid_until'] ?? null,
                'currency' => $data['currency'],
                ...CommercialDocumentFields::documentFromTotals($totals),
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                ...$this->snapshotsFor($data),
                'created_by' => $user->id,
            ]);

            $this->syncItems($quotation, $totals['items']);

            return $quotation->fresh(['items']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Quotation $quotation, array $data, User $user): Quotation
    {
        $this->assertEditable($quotation);

        $context = $this->taxContext($quotation->organization, $data);
        $totals = $this->calculator->calculateTotals($data['items'], $context);
        $previousTotal = (float) $quotation->total;
        $previousSubtotal = (float) $quotation->subtotal;

        DB::transaction(function () use ($quotation, $data, $totals, $user, $previousTotal, $previousSubtotal) {
            $quotation->update([
                'customer_id' => $data['customer_id'],
                'opportunity_id' => $data['opportunity_id'] ?? null,
                'title' => $data['title'] ?? null,
                'issue_date' => $data['issue_date'],
                'valid_until' => $data['valid_until'] ?? null,
                'currency' => $data['currency'],
                ...CommercialDocumentFields::documentFromTotals($totals),
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                ...$this->snapshotsFor($data),
            ]);

            $quotation->items()->delete();
            $this->syncItems($quotation, $totals['items']);

            if (
                round($previousTotal, 2) !== round((float) $totals['total'], 2)
                || round($previousSubtotal, 2) !== round($totals['subtotal'], 2)
            ) {
                $this->auditLogger->log($quotation, 'financial_recalculated', [
                    'previous_total' => $previousTotal,
                    'subtotal' => $totals['subtotal'],
                    'discount_amount' => $totals['discount_amount'],
                    'tax_total' => $totals['tax_total'],
                    'total' => $totals['total'],
                ], $user);
            }
        });

        return $quotation->fresh(['items']);
    }

    public function delete(Quotation $quotation, User $user): void
    {
        $this->assertDeletable($quotation);

        $quotation->delete();
    }

    public function updateStatus(Quotation $quotation, string $newStatus, User $user): Quotation
    {
        $currentStatus = $quotation->status;

        if ($currentStatus === $newStatus) {
            return $quotation;
        }

        $this->assertTransitionAllowed($currentStatus, $newStatus);

        if ($newStatus === 'accepted') {
            $this->assertCanAccept($quotation);
        }

        $previousStatus = $currentStatus;

        $event = match ($newStatus) {
            'sent' => 'sent',
            'accepted' => 'accepted',
            'rejected' => 'rejected',
            'expired' => 'expired',
            'converted' => 'converted',
            default => 'status_changed',
        };

        return DB::transaction(function () use ($quotation, $newStatus, $previousStatus, $event, $user) {
            $quotation->updateQuietly(['status' => $newStatus]);

            $this->auditLogger->log($quotation, $event, [
                'from' => $previousStatus,
                'to' => $newStatus,
            ], $user);

            return $quotation->fresh();
        });
    }

    public function markSentAfterEmail(Quotation $quotation, User $user): Quotation
    {
        if ($quotation->status !== 'draft') {
            return $quotation;
        }

        return $this->updateStatus($quotation, 'sent', $user);
    }

    public function recordEmailSent(Quotation $quotation, User $user, string $to): void
    {
        $this->auditLogger->log($quotation, 'sent', ['to' => $to], $user);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{billing_snapshot: array<string, mixed>, shipping_snapshot: array<string, mixed>}
     */
    protected function snapshotsFor(array $data): array
    {
        $customer = isset($data['customer_id'])
            ? Customer::query()->find($data['customer_id'])
            : null;

        return $this->partySnapshot->forCustomer($customer);
    }

    public function assertEditable(Quotation $quotation): void
    {
        if (! $quotation->isEditable()) {
            throw ValidationException::withMessages([
                'quotation' => [__('This quotation cannot be edited in its current status (:status).', [
                    'status' => $quotation->status_label,
                ])],
            ]);
        }
    }

    public function assertDeletable(Quotation $quotation): void
    {
        if (! $quotation->isDeletable()) {
            throw ValidationException::withMessages([
                'quotation' => [__('This quotation cannot be deleted in its current status (:status).', [
                    'status' => $quotation->status_label,
                ])],
            ]);
        }
    }

    public function assertTransitionAllowed(string $from, string $to): void
    {
        if (! Quotation::canTransition($from, $to)) {
            throw ValidationException::withMessages([
                'status' => [__('Cannot change quotation status from :from to :to.', [
                    'from' => config("quotations.statuses.{$from}", $from),
                    'to' => config("quotations.statuses.{$to}", $to),
                ])],
            ]);
        }
    }

    public function assertCanAccept(Quotation $quotation): void
    {
        $quotation->loadMissing(['customer', 'items']);

        if ($quotation->items->isEmpty()) {
            throw ValidationException::withMessages([
                'status' => [__('Cannot accept a quotation with no line items.')],
            ]);
        }

        if ((float) $quotation->total <= 0) {
            throw ValidationException::withMessages([
                'status' => [__('Cannot accept a quotation with zero or negative total.')],
            ]);
        }

        if (! $quotation->customer_id || ! $quotation->customer) {
            throw ValidationException::withMessages([
                'status' => [__('Cannot accept a quotation without a customer.')],
            ]);
        }

        if ($quotation->status === 'converted') {
            throw ValidationException::withMessages([
                'status' => [__('This quotation has already been converted.')],
            ]);
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

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function syncItems(Quotation $quotation, array $items): void
    {
        foreach ($items as $item) {
            $quotation->items()->create(CommercialDocumentFields::itemFromCalculated($item));
        }
    }
}
