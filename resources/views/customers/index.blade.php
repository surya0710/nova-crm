@php
    $statusColors = [
        'prospect' => 'bg-blue-100 text-blue-800',
        'active' => 'bg-emerald-100 text-emerald-800',
        'inactive' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ crm_term('customers') }}</h1>
                <p class="text-sm text-slate-500">{{ __('Manage your customer accounts') }}</p>
            </div>
            @can('create', App\Models\Customer::class)
                <a href="{{ route('customers.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition shrink-0">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('Add Customer') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6 space-y-3">
        <form method="GET" action="{{ route('customers.index') }}" id="customers-index-filters" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2">
                <x-text-input name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search name, company, email…') }}" class="w-full" />
            </div>
            <select name="status" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (config('customers.statuses') as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-text-input name="industry" :value="$filters['industry'] ?? ''" placeholder="{{ __('Industry') }}" class="w-full text-sm" />
            <div class="flex gap-2">
                <select name="assigned_to" class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('Anyone') }}</option>
                    @foreach ($assignees as $member)
                        <option value="{{ $member->id }}" @selected(($filters['assigned_to'] ?? '') == $member->id)>{{ $member->name }}</option>
                    @endforeach
                </select>
                <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
            </div>
            @include('metadata-fields._index_query_controls')
        </form>
        @include('metadata-fields._saved_filter_controls', ['filterFormId' => 'customers-index-filters'])
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($customers->isEmpty())
            <div class="p-12 text-center">
                <div class="mx-auto h-12 w-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">{{ __('No customers yet') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('Add your first customer to get started.') }}</p>
                @can('create', App\Models\Customer::class)
                    <a href="{{ route('customers.create') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                        {{ __('Add Customer') }} →
                    </a>
                @endcan
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Customer') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 hidden md:table-cell">{{ __('Industry') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 hidden lg:table-cell">{{ __('Account Manager') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 hidden lg:table-cell">{{ __('Location') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($customers as $customer)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-6 py-4">
                                    <a href="{{ route('customers.show', $customer) }}" class="group">
                                        <p class="text-sm font-semibold text-slate-900 group-hover:text-indigo-600">{{ $customer->display_name }}</p>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $customer->name }}@if($customer->email) · {{ $customer->email }}@endif</p>
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $statusColors[$customer->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $customer->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell text-sm text-slate-600">{{ $customer->industry ?? '—' }}</td>
                                <td class="px-6 py-4 hidden lg:table-cell text-sm text-slate-600">{{ $customer->assignee?->name ?? '—' }}</td>
                                <td class="px-6 py-4 hidden lg:table-cell text-sm text-slate-600">{{ $customer->city ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($customers->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">{{ $customers->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
