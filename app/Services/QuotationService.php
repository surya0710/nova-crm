<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuotationService
{
    public function __construct(
        protected QuotationCalculationService $calculator,
        protected AuditLogger $auditLogger,
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

        $totals = $this->calculator->calculateTotals($data['items']);

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
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_total' => $totals['tax_total'],
                'total' => $totals['total'],
                'notes' => $data['notes'] ?? null,
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

        $totals = $this->calculator->calculateTotals($data['items']);
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
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_total' => $totals['tax_total'],
                'total' => $totals['total'],
                'notes' => $data['notes'] ?? null,
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
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function syncItems(Quotation $quotation, array $items): void
    {
        foreach ($items as $item) {
            $quotation->items()->create([
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
}
