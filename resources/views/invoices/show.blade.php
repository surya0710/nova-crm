@php
    $statusColors = [
        'draft' => 'bg-slate-100 text-slate-700',
        'issued' => 'bg-blue-100 text-blue-800',
        'partially_paid' => 'bg-amber-100 text-amber-800',
        'paid' => 'bg-emerald-100 text-emerald-800',
        'cancelled' => 'bg-slate-100 text-slate-500',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-lg font-semibold text-slate-900">{{ $invoice->number }}</h1>
                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $statusColors[$invoice->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $invoice->status_label }}</span>
                </div>
                <p class="text-sm text-slate-500">{{ $invoice->customer->display_name }}@if($invoice->title) · {{ $invoice->title }}@endif</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @can('update', $invoice)
                    <a href="{{ route('invoices.edit', $invoice) }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">{{ __('Edit') }}</a>
                @endcan
                @can('issue', $invoice)
                    <form method="POST" action="{{ route('invoices.issue', $invoice) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition">
                            {{ __('Issue :label', ['label' => crm_term('invoice')]) }}
                        </button>
                    </form>
                @endcan
                @can('cancel', $invoice)
                    <form method="POST" action="{{ route('invoices.cancel', $invoice) }}" onsubmit="return confirm('{{ __('Cancel this invoice?') }}')">
                        @csrf
                        <button type="submit" class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100 transition">
                            {{ __('Cancel') }}
                        </button>
                    </form>
                @endcan
                @can('create', App\Models\Payment::class)
                    @if ($invoice->canAcceptPayment())
                        <a href="{{ route('payments.create', ['invoice' => $invoice->id]) }}" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                            {{ __('Record :label', ['label' => crm_term('payment')]) }}
                        </a>
                    @endif
                @endcan
                @can('delete', $invoice)
                    <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('{{ __('Delete this invoice?') }}')">
                        @csrf @method('DELETE')
                        <x-danger-button type="submit">{{ __('Delete') }}</x-danger-button>
                    </form>
                @endcan
            </div>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b bg-slate-50/50"><h3 class="font-semibold text-slate-900">{{ __('Line Items') }}</h3></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">{{ __('Description') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-slate-500">{{ __('Qty') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-slate-500">{{ __('Price') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-slate-500">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($invoice->items as $item)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-slate-900">{{ $item->description }}</td>
                                    <td class="px-6 py-4 text-sm text-right text-slate-600">{{ number_format((float) $item->quantity, 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-right text-slate-600">{{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-right font-medium">{{ number_format((float) $item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t bg-slate-50/30">
                    <dl class="space-y-2 text-sm max-w-xs ml-auto">
                        <div class="flex justify-between"><dt class="text-slate-500">{{ __('Subtotal') }}</dt><dd>{{ number_format((float) $invoice->subtotal, 2) }} {{ $invoice->currency }}</dd></div>
                        @if ((float) $invoice->discount_amount > 0)
                            <div class="flex justify-between"><dt class="text-slate-500">{{ __('Discount') }}</dt><dd>-{{ number_format((float) $invoice->discount_amount, 2) }} {{ $invoice->currency }}</dd></div>
                        @endif
                        @if ((float) $invoice->tax_total > 0)
                            <div class="flex justify-between"><dt class="text-slate-500">{{ __('Tax') }}</dt><dd>{{ number_format((float) $invoice->tax_total, 2) }} {{ $invoice->currency }}</dd></div>
                        @endif
                        <div class="flex justify-between border-t pt-2"><dt class="font-semibold text-slate-900">{{ __('Total') }}</dt><dd class="font-bold">{{ $invoice->formatted_total }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">{{ __('Paid') }}</dt><dd>{{ number_format((float) $invoice->amount_paid, 2) }} {{ $invoice->currency }}</dd></div>
                        <div class="flex justify-between border-t pt-2 font-semibold"><dt>{{ __('Balance Due') }}</dt><dd>{{ $invoice->formatted_balance_due }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b bg-slate-50/50 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900">{{ __('Payment History') }}</h3>
                    @if ($invoice->payments->isNotEmpty())
                        <a href="{{ route('payments.index', ['invoice_id' => $invoice->id]) }}" class="text-sm text-indigo-600">{{ __('View all') }}</a>
                    @endif
                </div>
                @if ($invoice->payments->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-slate-500">{{ __('No payments recorded yet.') }}</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">{{ crm_term('payment') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">{{ __('Date') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">{{ __('Method') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">{{ __('Reference') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-slate-500">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($invoice->payments as $payment)
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="px-6 py-4">
                                            <a href="{{ route('payments.show', $payment) }}" class="text-sm font-medium text-indigo-600">{{ $payment->number }}</a>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600">{{ $payment->payment_date->format('M j, Y') }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-600">{{ $payment->method_label }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-600">{{ $payment->reference ?? '—' }}</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-right text-slate-900">{{ $payment->formatted_amount }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

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
        </div>

        <div class="space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Details') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-xs text-slate-500">{{ crm_term('customer') }}</dt><dd class="mt-1"><a href="{{ route('customers.show', $invoice->customer) }}" class="text-indigo-600">{{ $invoice->customer->display_name }}</a></dd></div>
                    @if ($invoice->quotation)
                        <div>
                            <dt class="text-xs text-slate-500">{{ __('Generated from :label', ['label' => crm_term('quotation')]) }}</dt>
                            <dd class="mt-1">
                                <a href="{{ route('quotations.show', $invoice->quotation) }}" class="text-indigo-600 hover:text-indigo-800">{{ $invoice->quotation->number }}</a>
                            </dd>
                        </div>
                    @endif
                    <div><dt class="text-xs text-slate-500">{{ __('Issue Date') }}</dt><dd class="mt-1">{{ $invoice->issue_date->format('M j, Y') }}</dd></div>
                    @if ($invoice->due_date)
                        <div><dt class="text-xs text-slate-500">{{ __('Due Date') }}</dt><dd class="mt-1">{{ $invoice->due_date->format('M j, Y') }}</dd></div>
                    @endif
                    <div><dt class="text-xs text-slate-500">{{ __('Balance Due') }}</dt><dd class="mt-1 font-semibold text-slate-900">{{ $invoice->formatted_balance_due }}</dd></div>
                </dl>
            </div>
            <a href="{{ route('invoices.index') }}" class="text-sm font-medium text-indigo-600">← {{ __('Back to :label', ['label' => strtolower(crm_term('invoices'))]) }}</a>
        </div>
    </div>
</x-app-layout>
