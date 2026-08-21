<?php

namespace App\Services;

use App\Events\SalesOrderCreated;
use App\Events\SalesOrderConfirmed;
use App\Events\SalesOrderStatusChanged;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\Tax\CommercialDocumentFields;
use App\Services\Tax\TaxDeterminationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesOrderService
{
    public function __construct(
        protected SalesOrderCalculationService $calculator,
        protected TaxDeterminationService $taxDetermination,
        protected AuditLogger $auditLogger,
        protected CommercialPartySnapshot $partySnapshot,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, array $data, User $user): SalesOrder
    {
        if (($data['status'] ?? 'draft') !== 'draft') {
            throw ValidationException::withMessages([
                'status' => [__('New sales orders must be created as draft.')],
            ]);
        }

        $context = $this->taxContext($organization, $data);
        $totals = $this->calculator->calculateTotals($data['items'], $context);

        return $this->persistNewOrder($organization, $data, $totals, $user);
    }

    public function createFromQuotation(Quotation $quotation, User $user): SalesOrder
    {
        $quotation->loadMissing(['organization', 'items']);

        $snapshots = $this->partySnapshot->fromDocument($quotation);

        $data = [
            'customer_id' => $quotation->customer_id,
            'quotation_id' => $quotation->id,
            'opportunity_id' => $quotation->opportunity_id,
            'title' => $quotation->title,
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'expected_delivery_date' => ($quotation->valid_until ?? now()->addDays(14))->toDateString(),
            'currency' => $quotation->currency,
            'notes' => $this->quotationOrderNotes($quotation),
            'terms' => $quotation->terms,
            ...$snapshots,
        ];
        $totals = [
            ...CommercialDocumentFields::documentFromTotals($quotation->only(CommercialDocumentFields::documentKeys())),
            'items' => $quotation->items->map(fn ($item): array => CommercialDocumentFields::itemFromCalculated(
                $item->only(CommercialDocumentFields::itemKeys())
            ))->all(),
        ];

        return $this->persistNewOrder($quotation->organization, $data, $totals, $user);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $totals
     */
    protected function persistNewOrder(Organization $organization, array $data, array $totals, User $user): SalesOrder
    {
        return DB::transaction(function () use ($organization, $data, $totals, $user) {
            $order = SalesOrder::query()->create([
                'organization_id' => $organization->id,
                'number' => SalesOrder::generateNumber($organization),
                'customer_id' => $data['customer_id'],
                'quotation_id' => $data['quotation_id'] ?? null,
                'opportunity_id' => $data['opportunity_id'] ?? null,
                'title' => $data['title'] ?? null,
                'status' => 'draft',
                'order_date' => $data['order_date'],
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'currency' => $data['currency'],
                ...CommercialDocumentFields::documentFromTotals($totals),
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                ...$this->snapshotsFor($data),
                'created_by' => $user->id,
            ]);

            $this->syncItems($order, $totals['items']);
            $order = $order->fresh(['items']);
            event(SalesOrderCreated::forModel($order, ['actor_id' => $user->id]));

            return $order;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SalesOrder $salesOrder, array $data, User $user): SalesOrder
    {
        $this->assertEditable($salesOrder);

        $context = $this->taxContext($salesOrder->organization, $data);
        $totals = $this->calculator->calculateTotals($data['items'], $context);
        $previousTotal = (float) $salesOrder->total;
        $previousSubtotal = (float) $salesOrder->subtotal;

        DB::transaction(function () use ($salesOrder, $data, $totals, $user, $previousTotal, $previousSubtotal) {
            $salesOrder->update([
                'customer_id' => $data['customer_id'],
                'quotation_id' => $data['quotation_id'] ?? $salesOrder->quotation_id,
                'opportunity_id' => $data['opportunity_id'] ?? null,
                'title' => $data['title'] ?? null,
                'order_date' => $data['order_date'],
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'currency' => $data['currency'],
                ...CommercialDocumentFields::documentFromTotals($totals),
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                ...$this->snapshotsFor($data),
            ]);

            $salesOrder->items()->delete();
            $this->syncItems($salesOrder, $totals['items']);

            if (
                round($previousTotal, 2) !== round((float) $totals['total'], 2)
                || round($previousSubtotal, 2) !== round($totals['subtotal'], 2)
            ) {
                $this->auditLogger->log($salesOrder, 'financial_recalculated', [
                    'previous_total' => $previousTotal,
                    'subtotal' => $totals['subtotal'],
                    'discount_amount' => $totals['discount_amount'],
                    'tax_total' => $totals['tax_total'],
                    'total' => $totals['total'],
                ], $user);
            }
        });

        return $salesOrder->fresh(['items']);
    }

    public function delete(SalesOrder $salesOrder, User $user): void
    {
        $this->assertDeletable($salesOrder);

        $salesOrder->delete();
    }

    public function updateStatus(SalesOrder $salesOrder, string $newStatus, User $user): SalesOrder
    {
        $currentStatus = $salesOrder->status;

        if ($currentStatus === $newStatus) {
            return $salesOrder;
        }

        $this->assertTransitionAllowed($currentStatus, $newStatus);

        if ($newStatus === 'confirmed') {
            $this->assertCanConfirm($salesOrder);
        }

        $previousStatus = $currentStatus;

        $event = match ($newStatus) {
            'confirmed' => 'confirmed',
            'processing' => 'processing',
            'partially_fulfilled' => 'partially_fulfilled',
            'fulfilled' => 'fulfilled',
            'cancelled' => 'cancelled',
            default => 'status_changed',
        };

        return DB::transaction(function () use ($salesOrder, $newStatus, $previousStatus, $event, $user) {
            $salesOrder->updateQuietly(['status' => $newStatus]);

            $this->auditLogger->log($salesOrder, $event, [
                'from' => $previousStatus,
                'to' => $newStatus,
            ], $user);

            $salesOrder = $salesOrder->fresh();

            event(SalesOrderStatusChanged::forModel($salesOrder, [
                'from' => $previousStatus,
                'to' => $newStatus,
                'actor_id' => $user->id,
            ]));
            if ($newStatus === 'confirmed') {
                event(SalesOrderConfirmed::forModel($salesOrder, [
                    'from' => $previousStatus,
                    'actor_id' => $user->id,
                ]));
            }
            app(CommercialAutomationService::class)->notifySalesOrder($salesOrder, $previousStatus, $newStatus);

            return $salesOrder;
        });
    }

    public function recordEmailSent(SalesOrder $salesOrder, User $user, string $to): void
    {
        $this->auditLogger->log($salesOrder, 'sent', ['to' => $to], $user);
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

    public function assertEditable(SalesOrder $salesOrder): void
    {
        if (! $salesOrder->isEditable()) {
            throw ValidationException::withMessages([
                'sales_order' => [__('This sales order cannot be edited in its current status (:status).', [
                    'status' => $salesOrder->status_label,
                ])],
            ]);
        }
    }

    public function assertDeletable(SalesOrder $salesOrder): void
    {
        if (! $salesOrder->isDeletable()) {
            throw ValidationException::withMessages([
                'sales_order' => [__('This sales order cannot be deleted in its current status (:status).', [
                    'status' => $salesOrder->status_label,
                ])],
            ]);
        }

        if ($salesOrder->invoice()->where('status', '!=', 'cancelled')->exists()) {
            throw ValidationException::withMessages([
                'sales_order' => [__('Cannot delete a sales order that has an active invoice.')],
            ]);
        }
    }

    public function assertTransitionAllowed(string $from, string $to): void
    {
        if (! SalesOrder::canTransition($from, $to)) {
            throw ValidationException::withMessages([
                'status' => [__('Cannot change sales order status from :from to :to.', [
                    'from' => config("sales_orders.statuses.{$from}", $from),
                    'to' => config("sales_orders.statuses.{$to}", $to),
                ])],
            ]);
        }
    }

    public function assertCanConfirm(SalesOrder $salesOrder): void
    {
        $salesOrder->loadMissing(['customer', 'items']);

        if ($salesOrder->items->isEmpty()) {
            throw ValidationException::withMessages([
                'status' => [__('Cannot confirm a sales order with no line items.')],
            ]);
        }

        if ((float) $salesOrder->total <= 0) {
            throw ValidationException::withMessages([
                'status' => [__('Cannot confirm a sales order with zero or negative total.')],
            ]);
        }

        if (! $salesOrder->customer_id || ! $salesOrder->customer) {
            throw ValidationException::withMessages([
                'status' => [__('Cannot confirm a sales order without a customer.')],
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
    protected function syncItems(SalesOrder $salesOrder, array $items): void
    {
        foreach ($items as $item) {
            $salesOrder->items()->create(CommercialDocumentFields::itemFromCalculated($item));
        }
    }

    protected function quotationOrderNotes(Quotation $quotation): string
    {
        $reference = __('Created from quotation :number.', ['number' => $quotation->number]);

        return $quotation->notes ? $reference."\n\n".$quotation->notes : $reference;
    }
}
