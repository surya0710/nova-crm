@php
    $statusColors = [
        'draft' => 'bg-slate-100 text-slate-700',
        'sent' => 'bg-blue-100 text-blue-800',
        'accepted' => 'bg-emerald-100 text-emerald-800',
        'rejected' => 'bg-red-100 text-red-800',
        'expired' => 'bg-amber-100 text-amber-800',
        'converted' => 'bg-violet-100 text-violet-800',
    ];

    $allowedTransitions = $quotation->allowedTransitions();
    $selectableStatuses = array_unique(array_merge([$quotation->status], $allowedTransitions));
    $quickActions = [
        'sent' => ['label' => __('Mark Sent'), 'class' => 'border-blue-200 text-blue-700 hover:bg-blue-50'],
        'accepted' => ['label' => __('Accepted'), 'class' => 'border-emerald-200 text-emerald-700 hover:bg-emerald-50'],
        'rejected' => ['label' => __('Rejected'), 'class' => 'border-red-200 text-red-700 hover:bg-red-50'],
        'expired' => ['label' => __('Expired'), 'class' => 'border-amber-200 text-amber-700 hover:bg-amber-50'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-lg font-semibold text-slate-900">{{ $quotation->number }}</h1>
                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $statusColors[$quotation->status] ?? 'bg-slate-100 text-slate-600' }}">
                        {{ $quotation->status_label }}
                    </span>
                </div>
                <p class="text-sm text-slate-500">
                    {{ $quotation->customer->display_name }}
                    @if ($quotation->title)
                        · {{ $quotation->title }}
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @can('update', $quotation)
                    <a href="{{ route('quotations.edit', $quotation) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                        {{ __('Edit') }}
                    </a>
                @endcan
                @if ($quotation->canConvert())
                    @can('convert', $quotation)
                        <form method="POST" action="{{ route('quotations.convert', $quotation) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                                {{ __('Generate :label', ['label' => crm_term('invoice')]) }}
                            </button>
                        </form>
                    @endcan
                @elseif ($quotation->status === 'converted' && $quotation->invoice)
                    @can('view', $quotation->invoice)
                        <a href="{{ route('invoices.show', $quotation->invoice) }}" class="inline-flex items-center gap-2 rounded-lg border border-violet-200 bg-violet-50 px-4 py-2 text-sm font-medium text-violet-800 hover:bg-violet-100 transition">
                            {{ __('View :label', ['label' => crm_term('invoice')]) }}
                        </a>
                    @endcan
                @endif
                @can('delete', $quotation)
                    <form method="POST" action="{{ route('quotations.destroy', $quotation) }}" onsubmit="return confirm('{{ __('Delete this quotation?') }}')">
                        @csrf
                        @method('DELETE')
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
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Line Items') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Description') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Qty') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Price') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($quotation->items as $item)
                                <tr>
                                    <td class="px-6 py-4">
                                        <p class="text-sm text-slate-900">{{ $item->description }}</p>
                                        @if ($item->product)
                                            <p class="text-xs text-slate-500 mt-0.5">{{ $item->product->sku ?? $item->product->name }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600 text-right">{{ number_format((float) $item->quantity, 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-600 text-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-slate-900 text-right">{{ number_format((float) $item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                    <dl class="space-y-2 text-sm max-w-xs ml-auto">
                        <div class="flex justify-between">
                            <dt class="text-slate-500">{{ __('Subtotal') }}</dt>
                            <dd class="text-slate-900">{{ number_format((float) $quotation->subtotal, 2) }} {{ $quotation->currency }}</dd>
                        </div>
                        @if ((float) $quotation->discount_amount > 0)
                            <div class="flex justify-between">
                                <dt class="text-slate-500">{{ __('Discount') }}</dt>
                                <dd class="text-slate-900">-{{ number_format((float) $quotation->discount_amount, 2) }} {{ $quotation->currency }}</dd>
                            </div>
                        @endif
                        @if ((float) $quotation->tax_total > 0)
                            <div class="flex justify-between">
                                <dt class="text-slate-500">{{ __('Tax') }}</dt>
                                <dd class="text-slate-900">{{ number_format((float) $quotation->tax_total, 2) }} {{ $quotation->currency }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between border-t border-slate-200 pt-2">
                            <dt class="font-semibold text-slate-900">{{ __('Total') }}</dt>
                            <dd class="font-bold text-slate-900">{{ $quotation->formatted_total }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            @if ($quotation->notes)
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="font-semibold text-slate-900">{{ __('Terms & Notes') }}</h3>
                    </div>
                    <div class="p-6 text-sm text-slate-600 whitespace-pre-line">{{ $quotation->notes }}</div>
                </div>
            @endif

            @can('changeStatus', $quotation)
                <x-client-email-form
                    :action="route('quotations.send', $quotation)"
                    :email="old('email', $quotation->customer->email)"
                    :submit-label="__('Send Quotation')"
                    :description="__('Email this quotation to your customer')"
                    :organization="$organization"
                    :missing-email-hint="! $quotation->customer->email"
                />

                @if ($allowedTransitions !== [])
                    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                            <h3 class="font-semibold text-slate-900">{{ __('Update Status') }}</h3>
                        </div>
                        <div class="p-6">
                            <form method="POST" action="{{ route('quotations.status.update', $quotation) }}" class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-3">
                                @csrf
                                @method('PATCH')
                                <label for="quotation-status" class="text-xs font-semibold uppercase tracking-wide text-slate-500 shrink-0">{{ __('Status') }}</label>
                                <select
                                    id="quotation-status"
                                    name="status"
                                    onchange="this.form.submit()"
                                    class="text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm py-2 px-3 min-w-[160px]"
                                >
                                    @foreach ($selectableStatuses as $value)
                                        <option value="{{ $value }}" @selected($quotation->status === $value)>{{ config('quotations.statuses.'.$value) }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('status')" />
                            </form>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($quickActions as $status => $meta)
                                    @if (in_array($status, $allowedTransitions, true))
                                        <form method="POST" action="{{ route('quotations.status.update', $quotation) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $status }}">
                                            <button type="submit" class="text-xs font-medium px-2.5 py-1.5 rounded-lg border transition {{ $meta['class'] }}">
                                                {{ $meta['label'] }}
                                            </button>
                                        </form>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            @endcan

            <x-attachments-panel
                attachable-type="quotation"
                :attachable-id="$quotation->id"
                :attachments="$quotation->attachments"
                :can-upload="auth()->user()->can('attachments.create')"
                :can-delete="auth()->user()->can('attachments.delete')"
            />
        </div>

        <div class="space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Details') }}</h3>
                </div>
                <dl class="p-6 space-y-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Customer') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            <a href="{{ route('customers.show', $quotation->customer) }}" class="text-indigo-600 hover:text-indigo-800">{{ $quotation->customer->display_name }}</a>
                        </dd>
                    </div>
                    @if ($quotation->opportunity)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Deal') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900">
                                <a href="{{ route('pipeline.show', $quotation->opportunity) }}" class="text-indigo-600 hover:text-indigo-800">{{ $quotation->opportunity->title }}</a>
                            </dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Issue Date') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $quotation->issue_date->format('M j, Y') }}</dd>
                    </div>
                    @if ($quotation->valid_until)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Valid Until') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $quotation->valid_until->format('M j, Y') }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Created By') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $quotation->creator?->name ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <a href="{{ route('quotations.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-800">
                ← {{ __('Back to quotations') }}
            </a>
        </div>
    </div>
</x-app-layout>
