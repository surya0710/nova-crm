<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Support\Collection;

class CustomerRelationshipService
{
    /**
     * @return array<string, mixed>
     */
    public function hub(Customer $customer, User $user): array
    {
        $customer->loadCount([
            'contacts',
            'opportunities',
            'quotations',
            'salesOrders',
            'invoices',
            'payments',
            'tickets',
            'notes',
            'tasks',
            'attachments',
        ]);
        $openTickets = $customer->tickets()->open()->count();

        return [
            'counts' => [
                'contacts' => $customer->contacts_count,
                'opportunities' => $customer->opportunities_count,
                'quotations' => $customer->quotations_count,
                'sales_orders' => $customer->sales_orders_count,
                'invoices' => $customer->invoices_count,
                'payments' => $customer->payments_count,
                'tickets' => $openTickets,
                'notes' => $customer->notes_count,
                'tasks' => $customer->tasks_count,
                'documents' => $customer->attachments_count,
            ],
            'contacts' => $customer->contacts()->limit(8)->get(),
            'opportunities' => $user->hasPermission('opportunities.view')
                ? $customer->opportunities()->limit(5)->get()
                : collect(),
            'quotations' => $user->hasPermission('quotations.view')
                ? $customer->quotations()->limit(5)->get()
                : collect(),
            'sales_orders' => $user->hasPermission('sales_orders.view')
                ? $customer->salesOrders()->limit(5)->get()
                : collect(),
            'invoices' => $user->hasPermission('invoices.view')
                ? $customer->invoices()->limit(5)->get()
                : collect(),
            'payments' => $user->hasPermission('payments.view')
                ? $customer->payments()->limit(5)->get()
                : collect(),
            'tickets' => $customer->tickets()->with('assignee')->limit(5)->get(),
            'value' => $this->valueSummary($customer, $user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function valueSummary(Customer $customer, User $user): array
    {
        $currency = $customer->organization?->currency ?: 'INR';
        $canFinance = $user->hasPermission('finance.view') || $user->hasPermission('invoices.view');

        $openPipeline = Opportunity::query()
            ->where('customer_id', $customer->id)
            ->whereNotIn('stage', config('pipeline.closed_stages', ['closed_won', 'closed_lost']))
            ->sum('amount');

        $wonValue = Opportunity::query()
            ->where('customer_id', $customer->id)
            ->where('stage', 'closed_won')
            ->sum('amount');

        $invoiced = null;
        $outstanding = null;
        if ($canFinance) {
            $invoiceQuery = Invoice::query()
                ->where('customer_id', $customer->id)
                ->where('status', '!=', 'cancelled');
            $invoiced = (float) (clone $invoiceQuery)->sum('total');
            $paid = (float) (clone $invoiceQuery)->sum('amount_paid');
            $outstanding = max(0, $invoiced - $paid);
        }

        return [
            'currency' => $currency,
            'open_pipeline' => (float) $openPipeline,
            'won_value' => (float) $wonValue,
            'invoiced' => $invoiced,
            'outstanding' => $outstanding,
            'can_finance' => $canFinance,
        ];
    }

    /**
     * @return Collection<int, array{label: string, body: string, actor: ?string, timestamp: \Illuminate\Support\Carbon}>
     */
    public function contactTimeline(Contact $contact): Collection
    {
        return app(CommercialTimelineService::class)->forContact($contact);
    }
}

