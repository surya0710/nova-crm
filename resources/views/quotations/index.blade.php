@php
    $statusColors = [
        'draft' => 'bg-slate-100 text-slate-700',
        'sent' => 'bg-blue-100 text-blue-800',
        'accepted' => 'bg-emerald-100 text-emerald-800',
        'rejected' => 'bg-red-100 text-red-800',
        'expired' => 'bg-amber-100 text-amber-800',
        'converted' => 'bg-violet-100 text-violet-800',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ crm_term('quotations') }}</h1>
                <p class="text-sm text-slate-500">{{ __('Create and manage sales quotations') }}</p>
            </div>
            @can('create', App\Models\Quotation::class)
                <a href="{{ route('quotations.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition shrink-0">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('New Quotation') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6">
        <form method="GET" action="{{ route('quotations.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="lg:col-span-2">
                <x-text-input name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search number, title, customer…') }}" class="w-full" />
            </div>
            <select name="status" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (config('quotations.statuses') as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <select name="customer_id" class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('All customers') }}</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) ($filters['customer_id'] ?? '') === (string) $customer->id)>{{ $customer->display_name }}</option>
                    @endforeach
                </select>
                <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($quotations->isEmpty())
            <div class="p-12 text-center">
                <div class="mx-auto h-12 w-12 rounded-full bg-violet-50 text-violet-600 flex items-center justify-center mb-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">{{ __('No quotations yet') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('Create your first quotation for a customer.') }}</p>
                @can('create', App\Models\Quotation::class)
                    <a href="{{ route('quotations.create') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                        {{ __('New Quotation') }} →
                    </a>
                @endcan
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Quotation') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Customer') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Issue Date') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Total') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($quotations as $quotation)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-6 py-4">
                                    <a href="{{ route('quotations.show', $quotation) }}" class="group">
                                        <p class="text-sm font-semibold text-slate-900 group-hover:text-indigo-600">{{ $quotation->number }}</p>
                                        @if ($quotation->title)
                                            <p class="text-xs text-slate-500 mt-0.5">{{ $quotation->title }}</p>
                                        @endif
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $quotation->customer->display_name }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $quotation->issue_date->format('M j, Y') }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $quotation->formatted_total }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $statusColors[$quotation->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $quotation->status_label }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($quotations->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">{{ $quotations->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
