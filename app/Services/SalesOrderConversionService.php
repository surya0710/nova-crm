<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\User;
use App\Notifications\CrmNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesOrderConversionService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected InvoiceService $invoices,
    ) {}

    public function convert(SalesOrder $salesOrder, User $user): Invoice
    {
        return DB::transaction(function () use ($salesOrder, $user) {
            $salesOrder = SalesOrder::query()
                ->with(['customer', 'items', 'quotation', 'invoice'])
                ->whereKey($salesOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingInvoice = $this->findActiveInvoice($salesOrder);

            if ($existingInvoice) {
                return $existingInvoice;
            }

            $this->assertEligible($salesOrder);

            $invoice = $this->invoices->createFromSalesOrder($salesOrder, $user);

            $this->auditLogger->log($salesOrder, 'converted', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'sales_order_number' => $salesOrder->number,
                'quotation_id' => $salesOrder->quotation_id,
                'quotation_number' => $salesOrder->quotation?->number,
            ], $user);

            $this->auditLogger->log($invoice, 'created_from_sales_order', [
                'sales_order_id' => $salesOrder->id,
                'sales_order_number' => $salesOrder->number,
                'quotation_id' => $salesOrder->quotation_id,
                'quotation_number' => $salesOrder->quotation?->number,
                'invoice_number' => $invoice->number,
            ], $user);

            $this->notifyInternalUsers($salesOrder, $invoice, $user);

            return $invoice->fresh(['items', 'customer', 'quotation', 'salesOrder']);
        });
    }

    public function assertEligible(SalesOrder $salesOrder): void
    {
        $salesOrder->loadMissing(['customer', 'items']);

        if (! $salesOrder->canConvert()) {
            throw ValidationException::withMessages([
                'sales_order' => [__('This sales order cannot be converted to an invoice. Current status: :status.', [
                    'status' => $salesOrder->status_label,
                ])],
            ]);
        }

        if (! $salesOrder->customer_id || ! $salesOrder->customer) {
            throw ValidationException::withMessages([
                'sales_order' => [__('Cannot convert a sales order without a customer.')],
            ]);
        }

        if ($salesOrder->items->isEmpty()) {
            throw ValidationException::withMessages([
                'sales_order' => [__('Cannot convert a sales order with no line items.')],
            ]);
        }

        if ((float) $salesOrder->total <= 0) {
            throw ValidationException::withMessages([
                'sales_order' => [__('Cannot convert a sales order with zero or negative total.')],
            ]);
        }
    }

    protected function findActiveInvoice(SalesOrder $salesOrder): ?Invoice
    {
        return Invoice::query()
            ->where('sales_order_id', $salesOrder->id)
            ->where('status', '!=', 'cancelled')
            ->latest('id')
            ->first();
    }

    protected function notifyInternalUsers(SalesOrder $salesOrder, Invoice $invoice, User $converter): void
    {
        $creator = $salesOrder->creator;

        if (! $creator || $creator->id === $converter->id) {
            return;
        }

        if (! $creator->hasPermission('invoices.view', $salesOrder->organization)) {
            return;
        }

        $creator->notify(new CrmNotification(
            title: __('Invoice generated'),
            message: __('Invoice :invoice was generated from sales order :order.', [
                'invoice' => $invoice->number,
                'order' => $salesOrder->number,
            ]),
            actionUrl: route('invoices.show', $invoice),
            organizationId: $salesOrder->organization_id,
        ));
    }
}
