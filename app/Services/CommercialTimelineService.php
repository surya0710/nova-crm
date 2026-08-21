<?php

namespace App\Services;

use App\Models\AdjustmentNote;
use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\CrmActivity;
use App\Models\Customer;
use App\Models\CustomerTicket;
use App\Models\Invoice;
use App\Models\Opportunity;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CommercialTimelineService
{
    /**
     * @return Collection<int, array{
     *     type: string,
     *     event: string,
     *     label: string,
     *     body: string,
     *     actor: ?string,
     *     timestamp: \Illuminate\Support\Carbon,
     *     href: ?string
     * }>
     */
    public function forCustomer(Customer $customer, int $limit = 50): Collection
    {
        $customer->loadMissing(['notes.user']);

        $quotationIds = Quotation::query()->where('customer_id', $customer->id)->pluck('id');
        $salesOrderIds = SalesOrder::query()->where('customer_id', $customer->id)->pluck('id');
        $invoiceIds = Invoice::query()->where('customer_id', $customer->id)->pluck('id');
        $paymentIds = Payment::query()->where('customer_id', $customer->id)->pluck('id');
        $noteIds = AdjustmentNote::query()->where('customer_id', $customer->id)->pluck('id');

        $items = collect();

        foreach ($customer->notes as $note) {
            $items->push([
                'type' => 'note',
                'event' => 'note',
                'label' => __('Note'),
                'body' => $note->body,
                'actor' => $note->user?->name,
                'timestamp' => $note->created_at,
                'href' => null,
            ]);
        }

        $logs = collect();

        if ($quotationIds->isNotEmpty() || $salesOrderIds->isNotEmpty() || $invoiceIds->isNotEmpty() || $paymentIds->isNotEmpty() || $noteIds->isNotEmpty()) {
            $logs = AuditLog::query()
                ->with('user')
                ->where('organization_id', $customer->organization_id)
                ->where(function ($query) use ($quotationIds, $salesOrderIds, $invoiceIds, $paymentIds, $noteIds) {
                    if ($quotationIds->isNotEmpty()) {
                        $query->orWhere(function ($inner) use ($quotationIds) {
                            $inner->where('auditable_type', Quotation::class)
                                ->whereIn('auditable_id', $quotationIds);
                        });
                    }

                    if ($salesOrderIds->isNotEmpty()) {
                        $query->orWhere(function ($inner) use ($salesOrderIds) {
                            $inner->where('auditable_type', SalesOrder::class)
                                ->whereIn('auditable_id', $salesOrderIds);
                        });
                    }

                    if ($invoiceIds->isNotEmpty()) {
                        $query->orWhere(function ($inner) use ($invoiceIds) {
                            $inner->where('auditable_type', Invoice::class)
                                ->whereIn('auditable_id', $invoiceIds);
                        });
                    }

                    if ($paymentIds->isNotEmpty()) {
                        $query->orWhere(function ($inner) use ($paymentIds) {
                            $inner->where('auditable_type', Payment::class)
                                ->whereIn('auditable_id', $paymentIds);
                        });
                    }

                    if ($noteIds->isNotEmpty()) {
                        $query->orWhere(function ($inner) use ($noteIds) {
                            $inner->where('auditable_type', AdjustmentNote::class)
                                ->whereIn('auditable_id', $noteIds);
                        });
                    }
                })
                ->whereIn('event', [
                    'created',
                    'sent',
                    'accepted',
                    'rejected',
                    'expired',
                    'converted',
                    'confirmed',
                    'processing',
                    'partially_fulfilled',
                    'fulfilled',
                    'issued',
                    'cancelled',
                    'status_changed',
                    'created_from_quotation',
                    'created_from_sales_order',
                    'recorded',
                    'received',
                    'payment_applied',
                    'fully_paid',
                    'overpaid',
                    'applied',
                    'adjustment_applied',
                    'reminder_sent',
                    'portal_viewed',
                    'portal_accepted',
                    'portal_rejected',
                    'portal_paid',
                ])
                ->latest()
                ->limit($limit)
                ->get();
        }

        $quotations = Quotation::query()->whereIn('id', $quotationIds)->get()->keyBy('id');
        $salesOrders = SalesOrder::query()->whereIn('id', $salesOrderIds)->get()->keyBy('id');
        $invoices = Invoice::query()->whereIn('id', $invoiceIds)->get()->keyBy('id');
        $payments = Payment::query()->whereIn('id', $paymentIds)->get()->keyBy('id');
        $notes = AdjustmentNote::query()->whereIn('id', $noteIds)->get()->keyBy('id');

        foreach ($logs as $log) {
            $document = match ($log->auditable_type) {
                Quotation::class => $quotations->get($log->auditable_id),
                SalesOrder::class => $salesOrders->get($log->auditable_id),
                Invoice::class => $invoices->get($log->auditable_id),
                Payment::class => $payments->get($log->auditable_id),
                AdjustmentNote::class => $notes->get($log->auditable_id),
                default => null,
            };

            if (! $document) {
                continue;
            }

            $items->push([
                'type' => $this->typeFor($log),
                'event' => $log->event,
                'label' => $this->labelFor($log, $document),
                'body' => $this->bodyFor($log, $document),
                'actor' => $log->user?->name ?? data_get($log->properties, 'client_name'),
                'timestamp' => $log->created_at,
                'href' => $this->hrefFor($log, $document),
            ]);
        }

        $items = $items->concat($this->relatedCrmItems($customer, $limit));

        return $items
            ->sortByDesc(fn (array $item) => $item['timestamp']?->timestamp ?? 0)
            ->values();
    }

    /**
     * @return Collection<int, array{
     *     type: string,
     *     event: string,
     *     label: string,
     *     body: string,
     *     actor: ?string,
     *     timestamp: \Illuminate\Support\Carbon,
     *     href: ?string
     * }>
     */
    public function forContact(Contact $contact, int $limit = 50): Collection
    {
        $contact->loadMissing(['notes.user', 'customer', 'tasks.assignee', 'activities.creator', 'tickets']);

        $items = collect();

        foreach ($this->contactAuditItems($contact) as $item) {
            $items->push($item);
        }

        foreach ($contact->noteRecords() as $note) {
            $items->push([
                'type' => 'note',
                'event' => 'note',
                'label' => __('Note'),
                'body' => $note->body,
                'actor' => $note->user?->name,
                'timestamp' => $note->created_at,
                'href' => null,
            ]);
        }

        foreach ($contact->activities as $activity) {
            $items->push($this->activityItem($activity));
        }

        foreach ($contact->tasks as $task) {
            $items->push($this->taskItem($task));
        }

        if ($contact->customer) {
            $items = $items->concat($this->forCustomer($contact->customer, $limit));
        }

        return $items
            ->unique(fn (array $item) => ($item['type'] ?? '').'|'.($item['event'] ?? '').'|'.($item['body'] ?? '').'|'.($item['timestamp']?->timestamp ?? 0))
            ->sortByDesc(fn (array $item) => $item['timestamp']?->timestamp ?? 0)
            ->take($limit)
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function contactAuditItems(Contact $contact): Collection
    {
        $logs = AuditLog::query()
            ->with('user')
            ->where('organization_id', $contact->organization_id)
            ->where('auditable_type', $contact->getMorphClass())
            ->where('auditable_id', $contact->id)
            ->whereIn('event', ['created', 'updated', 'status_changed'])
            ->latest()
            ->limit(40)
            ->get();

        return $logs->map(function (AuditLog $log) {
            $changes = data_get($log->properties, 'changes', []);
            $changes = is_array($changes) ? $changes : [];
            $label = match (true) {
                $log->event === 'created' => __('Contact created'),
                array_key_exists('is_primary', $changes) && ($changes['is_primary'] === true || $changes['is_primary'] === 1) => __('Contact became primary'),
                array_key_exists('is_decision_maker', $changes) && ($changes['is_decision_maker'] === true || $changes['is_decision_maker'] === 1) => __('Contact became decision maker'),
                $log->event === 'status_changed' => __('Contact status changed'),
                default => __('Contact updated'),
            };

            $body = $log->event === 'created'
                ? ($log->subject ?: __('Contact'))
                : $this->contactChangeBody($changes, $log);

            return [
                'type' => 'contact',
                'event' => $log->event,
                'label' => $label,
                'body' => $body,
                'actor' => $log->user?->name,
                'timestamp' => $log->created_at,
                'href' => null,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    protected function contactChangeBody(array $changes, AuditLog $log): string
    {
        if (isset($changes['is_primary'])) {
            return $changes['is_primary'] ? __('Marked as primary contact') : __('Primary contact removed');
        }

        if (isset($changes['is_decision_maker'])) {
            return $changes['is_decision_maker'] ? __('Marked as decision maker') : __('Decision maker flag removed');
        }

        $from = data_get($log->properties, 'from');
        $to = data_get($log->properties, 'to');
        if ($from && $to) {
            return __(':from → :to', ['from' => $from, 'to' => $to]);
        }

        $keys = array_keys($changes);

        return $keys !== []
            ? __('Updated :fields', ['fields' => implode(', ', $keys)])
            : __('Contact updated');
    }

    /**
     * @return array<string, mixed>
     */
    protected function activityItem(CrmActivity $activity): array
    {
        $parts = array_filter([
            $activity->direction_label,
            $activity->outcome_label,
            $activity->duration_minutes ? __(':minutes min', ['minutes' => $activity->duration_minutes]) : null,
            $activity->body,
        ]);

        return [
            'type' => $activity->type,
            'event' => $activity->type,
            'label' => $activity->type_label,
            'body' => $activity->subject.($parts !== [] ? ' — '.implode(' · ', $parts) : ''),
            'actor' => $activity->creator?->name,
            'timestamp' => $activity->occurred_at ?? $activity->created_at,
            'href' => $activity->contact_id ? route('contacts.show', $activity->contact_id) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function taskItem(Task $task): array
    {
        return [
            'type' => 'task',
            'event' => 'task',
            'label' => __('Task'),
            'body' => $task->title.($task->due_at ? ' — '.$task->due_at->format('M j, Y') : ''),
            'actor' => $task->assignee?->name,
            'timestamp' => $task->created_at,
            'href' => route('tasks.show', $task),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function relatedCrmItems(Customer $customer, int $limit): Collection
    {
        $customer->loadMissing(['tasks.assignee', 'activities.creator']);

        $items = collect();

        foreach ($customer->activities as $activity) {
            $items->push($this->activityItem($activity));
        }

        foreach ($customer->tasks as $task) {
            $items->push($this->taskItem($task));
        }

        $opportunityIds = Opportunity::query()->where('customer_id', $customer->id)->pluck('id');
        $tickets = CustomerTicket::query()->where('customer_id', $customer->id)->with(['notes.user'])->get();
        $ticketIds = $tickets->pluck('id');

        foreach ($tickets as $ticket) {
            foreach ($ticket->notes as $note) {
                $items->push([
                    'type' => 'ticket_note',
                    'event' => 'note_added',
                    'label' => __('Ticket note').' · '.$ticket->number,
                    'body' => $note->body,
                    'actor' => $note->user?->name,
                    'timestamp' => $note->created_at,
                    'href' => route('tickets.show', $ticket),
                ]);
            }
        }

        if ($opportunityIds->isEmpty() && $ticketIds->isEmpty()) {
            return $items;
        }

        $logs = AuditLog::query()
            ->with('user')
            ->where('organization_id', $customer->organization_id)
            ->where(function ($query) use ($opportunityIds, $ticketIds) {
                if ($opportunityIds->isNotEmpty()) {
                    $query->orWhere(function ($inner) use ($opportunityIds) {
                        $inner->where('auditable_type', Opportunity::class)
                            ->whereIn('auditable_id', $opportunityIds);
                    });
                }

                if ($ticketIds->isNotEmpty()) {
                    $query->orWhere(function ($inner) use ($ticketIds) {
                        $inner->where('auditable_type', CustomerTicket::class)
                            ->whereIn('auditable_id', $ticketIds);
                    });
                }
            })
            ->whereIn('event', ['created', 'updated', 'status_changed', 'assigned'])
            ->latest()
            ->limit($limit)
            ->get();

        $opportunities = Opportunity::query()->whereIn('id', $opportunityIds)->get()->keyBy('id');
        $tickets = $tickets->keyBy('id');

        foreach ($logs as $log) {
            $document = match ($log->auditable_type) {
                Opportunity::class => $opportunities->get($log->auditable_id),
                CustomerTicket::class => $tickets->get($log->auditable_id),
                default => null,
            };

            if (! $document) {
                continue;
            }

            $items->push([
                'type' => $this->typeFor($log),
                'event' => $log->event,
                'label' => $this->labelFor($log, $document),
                'body' => $this->bodyFor($log, $document),
                'actor' => $log->user?->name,
                'timestamp' => $log->created_at,
                'href' => $this->hrefFor($log, $document),
            ]);
        }

        return $items;
    }

    protected function typeFor(AuditLog $log): string
    {
        return match ($log->auditable_type) {
            Quotation::class => 'quotation',
            SalesOrder::class => 'sales_order',
            Invoice::class => 'invoice',
            Payment::class => 'payment',
            AdjustmentNote::class => 'adjustment_note',
            Opportunity::class => 'opportunity',
            CustomerTicket::class => 'ticket',
            default => 'document',
        };
    }

    protected function labelFor(AuditLog $log, Model $document): string
    {
        $kind = match ($log->auditable_type) {
            Quotation::class => __('Quotation'),
            SalesOrder::class => __('Sales Order'),
            Invoice::class => __('Invoice'),
            Payment::class => __('Payment'),
            AdjustmentNote::class => $document instanceof AdjustmentNote && $document->type === 'debit'
                ? __('Debit Note')
                : __('Credit Note'),
            Opportunity::class => __('Opportunity'),
            CustomerTicket::class => __('Ticket'),
            default => __('Document'),
        };

        return match ($log->event) {
            'created' => __(':kind created', ['kind' => $kind]),
            'sent' => __(':kind sent', ['kind' => $kind]),
            'accepted' => __('Quotation accepted'),
            'rejected' => __('Quotation rejected'),
            'expired' => __('Quotation expired'),
            'converted' => $log->auditable_type === SalesOrder::class
                ? __('Sales order invoiced')
                : __('Quotation converted'),
            'confirmed' => __('Sales order confirmed'),
            'processing' => __('Sales order processing'),
            'partially_fulfilled' => __('Sales order partially fulfilled'),
            'fulfilled' => __('Sales order fulfilled'),
            'issued' => $log->auditable_type === AdjustmentNote::class
                ? __(':kind issued', ['kind' => $kind])
                : __('Invoice issued'),
            'cancelled' => __(':kind cancelled', ['kind' => $kind]),
            'created_from_quotation' => $log->auditable_type === SalesOrder::class
                ? __('Sales order created from quotation')
                : __('Invoice created from quotation'),
            'created_from_sales_order' => __('Invoice created from sales order'),
            'recorded' => __('Payment recorded'),
            'received' => __('Payment received'),
            'payment_applied' => __('Payment allocated'),
            'fully_paid' => __('Invoice fully paid'),
            'overpaid' => __('Invoice overpaid'),
            'applied' => __('Adjustment applied'),
            'adjustment_applied' => __('Adjustment applied to invoice'),
            'reminder_sent' => __('Reminder sent'),
            'portal_viewed', 'portal_accepted', 'portal_rejected', 'portal_paid' => __('Customer portal activity'),
            'status_changed' => __(':kind status changed', ['kind' => $kind]),
            default => $log->event_label,
        };
    }

    protected function bodyFor(AuditLog $log, Model $document): string
    {
        $number = $document->number
            ?? $document->title
            ?? $document->subject
            ?? (string) $document->getKey();
        $from = data_get($log->properties, 'from');
        $to = data_get($log->properties, 'to');

        if ($from && $to) {
            return __(':number — :from → :to', [
                'number' => $number,
                'from' => $from,
                'to' => $to,
            ]);
        }

        if ($log->event === 'created_from_quotation') {
            return __(':target from :quotation', [
                'target' => data_get($log->properties, 'sales_order_number', data_get($log->properties, 'invoice_number', $number)),
                'quotation' => data_get($log->properties, 'quotation_number', '—'),
            ]);
        }

        if ($log->event === 'created_from_sales_order') {
            return __(':invoice from :order', [
                'invoice' => data_get($log->properties, 'invoice_number', $number),
                'order' => data_get($log->properties, 'sales_order_number', '—'),
            ]);
        }

        if (in_array($log->event, ['recorded', 'received'], true)) {
            return __(':number of :amount', [
                'number' => $number,
                'amount' => $document instanceof Payment ? $document->formatted_amount : $number,
            ]);
        }

        if ($log->event === 'reminder_sent') {
            return __(':number — :type', [
                'number' => $number,
                'type' => data_get($log->properties, 'reminder_type', __('reminder')),
            ]);
        }

        if (str_starts_with($log->event, 'portal_')) {
            return __(':number (:action)', [
                'number' => $number,
                'action' => str_replace('portal_', '', $log->event),
            ]);
        }

        return $number;
    }

    protected function hrefFor(AuditLog $log, Model $document): ?string
    {
        return match ($log->auditable_type) {
            Quotation::class => route('quotations.show', $document),
            SalesOrder::class => route('sales-orders.show', $document),
            Invoice::class => route('invoices.show', $document),
            Payment::class => route('payments.show', $document),
            AdjustmentNote::class => $document instanceof AdjustmentNote
                ? route($document->routePrefix().'.show', $document)
                : null,
            Opportunity::class => route('pipeline.show', $document),
            CustomerTicket::class => route('tickets.show', $document),
            default => null,
        };
    }
}
