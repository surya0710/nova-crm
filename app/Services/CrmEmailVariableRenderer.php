<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\CustomerTicket;
use App\Models\Invoice;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Model;

class CrmEmailVariableRenderer
{
    public function interpolate(?string $text, array $context): string
    {
        $text = (string) $text;

        if ($text === '') {
            return '';
        }

        return (string) preg_replace_callback(
            '/\{\{\s*([a-z0-9_.]+)\s*\}\}/i',
            function (array $matches) use ($context) {
                $value = data_get($context, $matches[1]);

                return $value === null ? '' : (string) $value;
            },
            $text
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function contextFor(Organization $organization, ?Model $related = null): array
    {
        [$customer, $contact, $opportunity, $quotation, $salesOrder, $invoice, $payment, $ticket] = $this->entities($related);

        return [
            'customer' => [
                'name' => $customer?->name ?? '',
            ],
            'contact' => [
                'name' => $contact?->name ?? '',
            ],
            'company' => [
                'name' => $organization->name,
            ],
            'opportunity' => [
                'name' => $opportunity?->title ?? '',
            ],
            'quotation' => [
                'number' => $quotation?->number ?? '',
            ],
            'sales_order' => [
                'number' => $salesOrder?->number ?? '',
            ],
            'invoice' => [
                'number' => $invoice?->number ?? '',
                'total' => $invoice ? $invoice->formatted_total : '',
            ],
            'payment' => [
                'amount' => $payment ? number_format((float) $payment->amount, 2).' '.$payment->currency : '',
            ],
            'ticket' => [
                'number' => $ticket?->number ?? '',
                'subject' => $ticket?->subject ?? '',
            ],
        ];
    }

    /**
     * @return array{0: ?Customer, 1: ?Contact, 2: ?Opportunity, 3: ?Quotation, 4: ?SalesOrder, 5: ?Invoice, 6: ?Payment, 7: ?CustomerTicket}
     */
    protected function entities(?Model $related): array
    {
        $customer = null;
        $contact = null;
        $opportunity = null;
        $quotation = null;
        $salesOrder = null;
        $invoice = null;
        $payment = null;
        $ticket = null;

        if ($related instanceof Customer) {
            $customer = $related;
            $contact = $related->relationLoaded('primaryContact')
                ? $related->primaryContact
                : $related->primaryContact()->first();
        } elseif ($related instanceof Contact) {
            $contact = $related;
            $customer = $related->customer;
        } elseif ($related instanceof Opportunity) {
            $opportunity = $related;
            $customer = $related->customer;
            $contact = $related->customer?->primaryContact;
        } elseif ($related instanceof CustomerTicket) {
            $ticket = $related;
            $customer = $related->customer;
            $contact = $related->contact;
        } elseif ($related instanceof Quotation) {
            $quotation = $related;
            $customer = $related->customer;
        } elseif ($related instanceof SalesOrder) {
            $salesOrder = $related;
            $customer = $related->customer;
        } elseif ($related instanceof Invoice) {
            $invoice = $related;
            $customer = $related->customer;
        } elseif ($related instanceof Payment) {
            $payment = $related;
            $invoice = $related->invoice;
            $customer = $related->customer;
        } elseif ($related && method_exists($related, 'customer')) {
            $customer = $related->customer;
        }

        if ($customer && ! $contact) {
            $contact = $customer->relationLoaded('primaryContact')
                ? $customer->primaryContact
                : $customer->primaryContact()->first();
        }

        return [$customer, $contact, $opportunity, $quotation, $salesOrder, $invoice, $payment, $ticket];
    }
}
