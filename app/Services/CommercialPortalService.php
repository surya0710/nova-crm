<?php

namespace App\Services;

use App\Models\AdjustmentNote;
use App\Models\ClientUser;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;

class CommercialPortalService
{
    public function customerFor(ClientUser $client): ?Customer
    {
        if (! $client->customer_id) {
            return null;
        }

        return Customer::query()
            ->where('organization_id', $client->organization_id)
            ->whereKey($client->customer_id)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(ClientUser $client): array
    {
        $customer = $this->customerFor($client);

        if (! $customer) {
            return [
                'linked' => false,
                'outstanding' => 0,
                'quotations' => 0,
                'sales_orders' => 0,
                'invoices' => 0,
                'payments' => 0,
                'notes' => 0,
            ];
        }

        $ledger = app(RevenueService::class)->customerStatement($customer);

        return [
            'linked' => true,
            'customer' => $customer,
            'outstanding' => $ledger['outstanding_balance'],
            'currency' => $ledger['currency'],
            'quotations' => Quotation::query()->where('customer_id', $customer->id)->where('status', '!=', 'draft')->count(),
            'sales_orders' => SalesOrder::query()->where('customer_id', $customer->id)->where('status', '!=', 'draft')->count(),
            'invoices' => Invoice::query()->where('customer_id', $customer->id)->whereNotIn('status', ['draft', 'cancelled'])->count(),
            'payments' => Payment::query()->where('customer_id', $customer->id)->count(),
            'notes' => AdjustmentNote::query()->where('customer_id', $customer->id)->whereIn('status', ['issued', 'applied'])->count(),
            'ledger' => $ledger,
        ];
    }

    /**
     * @template TModel of Model
     * @param  class-string<TModel>  $class
     * @return TModel
     */
    public function scopedFind(ClientUser $client, string $class, int $id): Model
    {
        $customer = $this->customerFor($client);
        abort_unless($customer, 404);

        $record = $class::query()
            ->where('organization_id', $client->organization_id)
            ->where('customer_id', $customer->id)
            ->whereKey($id)
            ->first();

        abort_unless($record, 404);

        return $record;
    }

    public function visibleQuotations(ClientUser $client)
    {
        $customer = $this->requireCustomer($client);

        return Quotation::query()
            ->where('customer_id', $customer->id)
            ->where('status', '!=', 'draft')
            ->latest('issue_date')
            ->get();
    }

    public function visibleSalesOrders(ClientUser $client)
    {
        $customer = $this->requireCustomer($client);

        return SalesOrder::query()
            ->where('customer_id', $customer->id)
            ->where('status', '!=', 'draft')
            ->latest('order_date')
            ->get();
    }

    public function visibleInvoices(ClientUser $client)
    {
        $customer = $this->requireCustomer($client);

        return Invoice::query()
            ->where('customer_id', $customer->id)
            ->whereNotIn('status', ['draft'])
            ->latest('issue_date')
            ->get();
    }

    public function visiblePayments(ClientUser $client)
    {
        $customer = $this->requireCustomer($client);

        return Payment::query()
            ->where('customer_id', $customer->id)
            ->latest('payment_date')
            ->get();
    }

    public function visibleNotes(ClientUser $client)
    {
        $customer = $this->requireCustomer($client);

        return AdjustmentNote::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['issued', 'applied'])
            ->latest('issue_date')
            ->get();
    }

    public function requireCustomer(ClientUser $client): Customer
    {
        $customer = $this->customerFor($client);
        abort_unless($customer, 404);

        return $customer;
    }

    public function outstandingFor(Invoice $invoice): float
    {
        return max(0, Money::round($invoice->effective_balance));
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function recordActivity(Model $document, string $event, ClientUser $client, array $properties = []): void
    {
        app(AuditLogger::class)->log($document, $event, array_merge($properties, [
            'portal' => true,
            'client_user_id' => $client->id,
            'client_name' => $client->name,
        ]));
    }
}
