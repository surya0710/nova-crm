<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quotation;
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
        $invoiceIds = Invoice::query()->where('customer_id', $customer->id)->pluck('id');

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

        if ($quotationIds->isNotEmpty() || $invoiceIds->isNotEmpty()) {
            $logs = AuditLog::query()
                ->with('user')
                ->where(function ($query) use ($quotationIds, $invoiceIds) {
                    if ($quotationIds->isNotEmpty()) {
                        $query->orWhere(function ($inner) use ($quotationIds) {
                            $inner->where('auditable_type', Quotation::class)
                                ->whereIn('auditable_id', $quotationIds);
                        });
                    }

                    if ($invoiceIds->isNotEmpty()) {
                        $query->orWhere(function ($inner) use ($invoiceIds) {
                            $inner->where('auditable_type', Invoice::class)
                                ->whereIn('auditable_id', $invoiceIds);
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
                    'issued',
                    'cancelled',
                    'status_changed',
                    'created_from_quotation',
                ])
                ->latest()
                ->limit($limit)
                ->get();
        }

        $quotations = Quotation::query()->whereIn('id', $quotationIds)->get()->keyBy('id');
        $invoices = Invoice::query()->whereIn('id', $invoiceIds)->get()->keyBy('id');

        foreach ($logs as $log) {
            $isQuotation = $log->auditable_type === Quotation::class;
            $document = $isQuotation
                ? $quotations->get($log->auditable_id)
                : $invoices->get($log->auditable_id);

            if (! $document) {
                continue;
            }

            $items->push([
                'type' => $isQuotation ? 'quotation' : 'invoice',
                'event' => $log->event,
                'label' => $this->labelFor($log, $isQuotation),
                'body' => $this->bodyFor($log, $document),
                'actor' => $log->user?->name,
                'timestamp' => $log->created_at,
                'href' => $isQuotation
                    ? route('quotations.show', $document)
                    : route('invoices.show', $document),
            ]);
        }

        return $items
            ->sortByDesc(fn (array $item) => $item['timestamp']?->timestamp ?? 0)
            ->values();
    }

    protected function labelFor(AuditLog $log, bool $isQuotation): string
    {
        $kind = $isQuotation ? __('Quotation') : __('Invoice');

        return match ($log->event) {
            'created' => __(':kind created', ['kind' => $kind]),
            'sent' => __(':kind sent', ['kind' => $kind]),
            'accepted' => __('Quotation accepted'),
            'rejected' => __('Quotation rejected'),
            'expired' => __('Quotation expired'),
            'converted' => __('Quotation converted'),
            'issued' => __('Invoice issued'),
            'cancelled' => __('Invoice cancelled'),
            'created_from_quotation' => __('Invoice created from quotation'),
            'status_changed' => __(':kind status changed', ['kind' => $kind]),
            default => $log->event_label,
        };
    }

    protected function bodyFor(AuditLog $log, Quotation|Invoice $document): string
    {
        $number = $document->number;
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
            return __(':invoice from :quotation', [
                'invoice' => data_get($log->properties, 'invoice_number', $number),
                'quotation' => data_get($log->properties, 'quotation_number', '—'),
            ]);
        }

        return $number;
    }
}
