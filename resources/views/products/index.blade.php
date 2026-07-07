@php
    $statusColors = [
        'active' => 'bg-emerald-100 text-emerald-800',
        'inactive' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ crm_term('products') }}</h1>
                <p class="text-sm text-slate-500">{{ __('Manage your product and service catalog') }}</p>
            </div>
            @can('create', App\Models\Product::class)
                <a href="{{ route('products.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition shrink-0">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('Add Product') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6">
        <form method="GET" action="{{ route('products.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2">
                <x-text-input name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search name, SKU, category…') }}" class="w-full" />
            </div>
            <select name="status" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (config('products.statuses') as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="type" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All types') }}</option>
                @foreach (config('products.types') as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <x-text-input name="category" :value="$filters['category'] ?? ''" placeholder="{{ __('Category') }}" class="flex-1 text-sm" />
                <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($products->isEmpty())
            <div class="p-12 text-center">
                <div class="mx-auto h-12 w-12 rounded-full bg-violet-50 text-violet-600 flex items-center justify-center mb-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">{{ __('No products yet') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('Add products and services for quotations and invoices.') }}</p>
                @can('create', App\Models\Product::class)
                    <a href="{{ route('products.create') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                        {{ __('Add Product') }} →
                    </a>
                @endcan
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Product') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Type') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Price') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 hidden md:table-cell">{{ __('Category') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($products as $product)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-6 py-4">
                                    <a href="{{ route('products.show', $product) }}" class="group">
                                        <p class="text-sm font-semibold text-slate-900 group-hover:text-indigo-600">{{ $product->name }}</p>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $product->sku ?? '—' }}</p>
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $product->type_label }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $product->formatted_price }}@if($product->unit_label)<span class="text-slate-500 font-normal"> / {{ $product->unit_label }}</span>@endif</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $statusColors[$product->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $product->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell text-sm text-slate-600">{{ $product->category ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($products->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">{{ $products->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
