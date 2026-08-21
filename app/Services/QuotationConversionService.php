<?php

namespace App\Services;

use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use App\Notifications\CrmNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuotationConversionService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected SalesOrderService $salesOrders,
    ) {}

    public function convert(Quotation $quotation, User $user): SalesOrder
    {
        return DB::transaction(function () use ($quotation, $user) {
            $quotation = Quotation::query()
                ->with(['customer', 'items', 'salesOrder'])
                ->whereKey($quotation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingOrder = $this->findActiveSalesOrder($quotation);

            if ($existingOrder) {
                return $existingOrder;
            }

            $this->assertEligible($quotation);

            $salesOrder = $this->salesOrders->createFromQuotation($quotation, $user);

            $previousStatus = $quotation->status;

            $quotation->updateQuietly(['status' => 'converted']);

            $this->auditLogger->log($quotation, 'converted', [
                'from' => $previousStatus,
                'to' => 'converted',
                'sales_order_id' => $salesOrder->id,
                'sales_order_number' => $salesOrder->number,
                'quotation_number' => $quotation->number,
            ], $user);

            $this->auditLogger->log($salesOrder, 'created_from_quotation', [
                'quotation_id' => $quotation->id,
                'quotation_number' => $quotation->number,
                'sales_order_number' => $salesOrder->number,
            ], $user);

            $this->notifyInternalUsers($quotation, $salesOrder, $user);

            return $salesOrder->fresh(['items', 'customer', 'quotation']);
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
                'quotation' => [__('Only accepted quotations can be converted to a sales order. Current status: :status.', [
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

    protected function findActiveSalesOrder(Quotation $quotation): ?SalesOrder
    {
        return SalesOrder::query()
            ->where('quotation_id', $quotation->id)
            ->where('status', '!=', 'cancelled')
            ->latest('id')
            ->first();
    }

    protected function notifyInternalUsers(Quotation $quotation, SalesOrder $salesOrder, User $converter): void
    {
        $creator = $quotation->creator;

        if (! $creator || $creator->id === $converter->id) {
            return;
        }

        if (! $creator->hasPermission('sales_orders.view', $quotation->organization)) {
            return;
        }

        $creator->notify(new CrmNotification(
            title: __('Sales order generated'),
            message: __('Sales order :order was generated from quotation :quotation.', [
                'order' => $salesOrder->number,
                'quotation' => $quotation->number,
            ]),
            actionUrl: route('sales-orders.show', $salesOrder),
            organizationId: $quotation->organization_id,
        ));
    }
}
