@php
    $statusVariant = [
        'draft' => 'neutral',
        'issued' => 'info',
        'partially_paid' => 'warning',
        'paid' => 'success',
        'cancelled' => 'neutral',
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$invoice->number"
        :subtitle="collect([$invoice->customer->display_name, $invoice->title])->filter()->implode(' · ')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('invoices'), 'href' => route('invoices.index')],
                ['label' => $invoice->number, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('update', $invoice)
                <x-ui.button :href="route('invoices.edit', $invoice)" variant="secondary" size="sm">{{ __('Edit') }}</x-ui.button>
            @endcan
            @can('issue', $invoice)
                <form method="POST" action="{{ route('invoices.issue', $invoice) }}">
                    @csrf
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Issue :label', ['label' => crm_term('invoice')]) }}</x-ui.button>
                </form>
            @endcan
            @can('cancel', $invoice)
                <form method="POST" action="{{ route('invoices.cancel', $invoice) }}" onsubmit="return confirm('{{ __('Cancel this invoice?') }}')">
                    @csrf
                    <x-ui.button type="submit" variant="danger" size="sm">{{ __('Cancel') }}</x-ui.button>
                </form>
            @endcan
            @can('create', App\Models\Payment::class)
                @if ($invoice->canAcceptPayment())
                    <x-ui.button :href="route('payments.create', ['invoice' => $invoice->id])" variant="primary" size="sm">{{ __('Record :label', ['label' => crm_term('payment')]) }}</x-ui.button>
                @endif
            @endcan
            @can('delete', $invoice)
                <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('{{ __('Delete this invoice?') }}')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger" size="sm">{{ __('Delete') }}</x-ui.button>
                </form>
            @endcan
        </x-slot:actions>

        <x-slot:tabs>
            <x-ui.badge :variant="$statusVariant[$invoice->status] ?? 'neutral'">{{ $invoice->status_label }}</x-ui.badge>
        </x-slot:tabs>

        <x-entity.section :title="__('Line Items')">
            <x-tables.table :columns="[
                __('Description'),
                ['label' => __('Qty'), 'align' => 'right'],
                ['label' => __('Price'), 'align' => 'right'],
                ['label' => __('Total'), 'align' => 'right'],
            ]" :sticky="false">
                @foreach ($invoice->items as $item)
                    <tr>
                        <td class="px-4 py-3 text-sm text-ink-heading">{{ $item->description }}</td>
                        <td class="px-4 py-3 text-right text-sm text-ink-muted">{{ number_format((float) $item->quantity, 2) }}</td>
                        <td class="px-4 py-3 text-right text-sm text-ink-muted">{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-ink-heading">{{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
            <dl class="mt-4 max-w-xs ms-auto space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-ink-muted">{{ __('Subtotal') }}</dt>
                    <dd class="text-ink-heading">{{ number_format((float) $invoice->subtotal, 2) }} {{ $invoice->currency }}</dd>
                </div>
                @if ((float) $invoice->discount_amount > 0)
                    <div class="flex justify-between">
                        <dt class="text-ink-muted">{{ __('Discount') }}</dt>
                        <dd class="text-ink-heading">-{{ number_format((float) $invoice->discount_amount, 2) }} {{ $invoice->currency }}</dd>
                    </div>
                @endif
                @if ((float) $invoice->tax_total > 0)
                    <div class="flex justify-between">
                        <dt class="text-ink-muted">{{ __('Tax') }}</dt>
                        <dd class="text-ink-heading">{{ number_format((float) $invoice->tax_total, 2) }} {{ $invoice->currency }}</dd>
                    </div>
                @endif
                <div class="flex justify-between border-t border-line pt-2">
                    <dt class="font-semibold text-ink-heading">{{ __('Total') }}</dt>
                    <dd class="font-bold text-ink-heading">{{ $invoice->formatted_total }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-ink-muted">{{ __('Paid') }}</dt>
                    <dd class="text-ink-heading">{{ number_format((float) $invoice->amount_paid, 2) }} {{ $invoice->currency }}</dd>
                </div>
                <div class="flex justify-between border-t border-line pt-2 font-semibold">
                    <dt class="text-ink-heading">{{ __('Balance Due') }}</dt>
                    <dd class="text-ink-heading">{{ $invoice->formatted_balance_due }}</dd>
                </div>
            </dl>
        </x-entity.section>

        <x-entity.section :title="__('Payment History')">
            <x-slot:actions>
                @if ($invoice->payments->isNotEmpty())
                    <x-ui.button :href="route('payments.index', ['invoice_id' => $invoice->id])" variant="link" size="sm">{{ __('View all') }}</x-ui.button>
                @endif
            </x-slot:actions>
            @if ($invoice->payments->isEmpty())
                <p class="py-6 text-center text-sm text-ink-muted">{{ __('No payments recorded yet.') }}</p>
            @else
                <x-tables.table :columns="[
                    crm_term('payment'),
                    __('Date'),
                    __('Method'),
                    __('Reference'),
                    ['label' => __('Amount'), 'align' => 'right'],
                ]" :sticky="false">
                    @foreach ($invoice->payments as $payment)
                        <tr class="hover:bg-surface-muted/60">
                            <td class="px-4 py-3">
                                <a href="{{ route('payments.show', $payment) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">{{ $payment->number }}</a>
                            </td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ $payment->payment_date->format('M j, Y') }}</td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ $payment->method_label }}</td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ $payment->reference ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-ink-heading">{{ $payment->formatted_amount }}</td>
                        </tr>
                    @endforeach
                </x-tables.table>
            @endif
        </x-entity.section>

        @can('send', $invoice)
            @if ($invoice->status !== 'cancelled')
                <x-client-email-form
                    :action="route('invoices.send', $invoice)"
                    :email="old('email', $invoice->customer->email)"
                    :submit-label="__('Send :label', ['label' => crm_term('invoice')])"
                    :description="__('Email this invoice to your customer')"
                    :organization="$organization"
                    :missing-email-hint="! $invoice->customer->email"
                />
            @endif
        @endcan

        <x-attachments-panel
            attachable-type="invoice"
            :attachable-id="$invoice->id"
            :attachments="$invoice->attachments"
            :can-upload="auth()->user()->can('attachments.create')"
            :can-delete="auth()->user()->can('attachments.delete')"
        />

        <x-slot:aside>
            <x-entity.section :title="__('Details')">
                <x-entity.definition-list>
                    <x-entity.definition-item :label="crm_term('customer')" :span="2">
                        <a href="{{ route('customers.show', $invoice->customer) }}" class="text-primary-600 hover:text-primary-700">{{ $invoice->customer->display_name }}</a>
                    </x-entity.definition-item>
                    @if ($invoice->quotation)
                        <x-entity.definition-item :label="__('Generated from :label', ['label' => crm_term('quotation')])" :span="2">
                            <a href="{{ route('quotations.show', $invoice->quotation) }}" class="text-primary-600 hover:text-primary-700">{{ $invoice->quotation->number }}</a>
                        </x-entity.definition-item>
                    @endif
                    <x-entity.definition-item :label="__('Issue Date')">{{ $invoice->issue_date->format('M j, Y') }}</x-entity.definition-item>
                    @if ($invoice->due_date)
                        <x-entity.definition-item :label="__('Due Date')">{{ $invoice->due_date->format('M j, Y') }}</x-entity.definition-item>
                    @endif
                    <x-entity.definition-item :label="__('Balance Due')" :span="2">
                        <span class="font-semibold text-ink-heading">{{ $invoice->formatted_balance_due }}</span>
                    </x-entity.definition-item>
                </x-entity.definition-list>
            </x-entity.section>
            <x-ui.button :href="route('invoices.index')" variant="link" size="sm">← {{ __('Back to :label', ['label' => strtolower(crm_term('invoices'))]) }}</x-ui.button>
        </x-slot:aside>
    </x-layouts.entity-detail>
</x-app-layout>
