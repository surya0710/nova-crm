@php
    $statusColors = [
        'draft' => 'bg-slate-100 text-slate-700',
        'sent' => 'bg-blue-100 text-blue-800',
        'partial' => 'bg-amber-100 text-amber-800',
        'paid' => 'bg-emerald-100 text-emerald-800',
        'overdue' => 'bg-red-100 text-red-800',
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
                        <div class="flex justify-between"><dt class="text-slate-500">{{ __('Total') }}</dt><dd>{{ $invoice->formatted_total }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">{{ __('Paid') }}</dt><dd>{{ number_format((float) $invoice->amount_paid, 2) }} {{ $invoice->currency }}</dd></div>
                        <div class="flex justify-between border-t pt-2 font-semibold"><dt>{{ __('Balance Due') }}</dt><dd>{{ $invoice->formatted_balance_due }}</dd></div>
                    </dl>
                </div>
            </div>

            @can('update', $invoice)
                @if ($invoice->payments->isNotEmpty())
                    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b bg-slate-50/50 flex items-center justify-between">
                            <h3 class="font-semibold text-slate-900">{{ crm_term('payments') }}</h3>
                            <a href="{{ route('payments.index', ['invoice_id' => $invoice->id]) }}" class="text-sm text-indigo-600">{{ __('View all') }}</a>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @foreach ($invoice->payments as $payment)
                                <div class="px-6 py-3 flex items-center justify-between gap-3">
                                    <div>
                                        <a href="{{ route('payments.show', $payment) }}" class="text-sm font-medium text-indigo-600">{{ $payment->number }}</a>
                                        <p class="text-xs text-slate-500">{{ $payment->payment_date->format('M j, Y') }} · {{ $payment->method_label }}</p>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-900">{{ $payment->formatted_amount }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($invoice->balance_due > 0 && $invoice->status !== 'cancelled')
                    @can('create', App\Models\Payment::class)
                        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b bg-slate-50/50"><h3 class="font-semibold text-slate-900">{{ __('Record :label', ['label' => crm_term('payment')]) }}</h3></div>
                            <form method="POST" action="{{ route('payments.store') }}" class="p-6 space-y-4">
                                @csrf
                                <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="amount" :value="__('Amount')" />
                                        <x-text-input id="amount" class="block mt-1 w-full" type="number" step="0.01" min="0.01" :max="$invoice->balance_due" name="amount" :value="old('amount', $invoice->balance_due)" required />
                                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="payment_date" :value="__('Payment Date')" />
                                        <x-text-input id="payment_date" class="block mt-1 w-full" type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required />
                                    </div>
                                    <div>
                                        <x-input-label for="method" :value="__('Method')" />
                                        <select id="method" name="method" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                            @foreach (config('payments.methods') as $value => $label)
                                                <option value="{{ $value }}" @selected(old('method') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <x-input-label for="reference" :value="__('Reference')" />
                                        <x-text-input id="reference" class="block mt-1 w-full" type="text" name="reference" :value="old('reference')" />
                                    </div>
                                </div>
                                <x-primary-button type="submit">{{ __('Record :label', ['label' => crm_term('payment')]) }}</x-primary-button>
                            </form>
                        </div>
                    @endcan
                @endif

                <x-client-email-form
                    :action="route('invoices.send', $invoice)"
                    :email="old('email', $invoice->customer->email)"
                    :submit-label="__('Send :label', ['label' => crm_term('invoice')])"
                    :description="__('Email this invoice to your customer')"
                    :organization="$organization"
                    :missing-email-hint="! $invoice->customer->email"
                />

                <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b bg-slate-50/50"><h3 class="font-semibold text-slate-900">{{ __('Update Status') }}</h3></div>
                    <div class="p-6">
                        <form method="POST" action="{{ route('invoices.status.update', $invoice) }}" class="flex flex-wrap items-center gap-3">
                            @csrf @method('PATCH')
                            <select name="status" onchange="this.form.submit()" class="text-sm border-gray-300 rounded-lg py-2 px-3">
                                @foreach (config('invoices.statuses') as $value => $label)
                                    <option value="{{ $value }}" @selected($invoice->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>
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
                        <div><dt class="text-xs text-slate-500">{{ crm_term('quotation') }}</dt><dd class="mt-1"><a href="{{ route('quotations.show', $invoice->quotation) }}" class="text-indigo-600">{{ $invoice->quotation->number }}</a></dd></div>
                    @endif
                    <div><dt class="text-xs text-slate-500">{{ __('Issue Date') }}</dt><dd class="mt-1">{{ $invoice->issue_date->format('M j, Y') }}</dd></div>
                    @if ($invoice->due_date)
                        <div><dt class="text-xs text-slate-500">{{ __('Due Date') }}</dt><dd class="mt-1">{{ $invoice->due_date->format('M j, Y') }}</dd></div>
                    @endif
                </dl>
            </div>
            <a href="{{ route('invoices.index') }}" class="text-sm font-medium text-indigo-600">← {{ __('Back to :label', ['label' => strtolower(crm_term('invoices'))]) }}</a>
        </div>
    </div>
</x-app-layout>
