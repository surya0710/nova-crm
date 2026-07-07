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
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-lg font-semibold text-slate-900">{{ $product->name }}</h1>
                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $statusColors[$product->status] ?? 'bg-slate-100 text-slate-600' }}">
                        {{ $product->status_label }}
                    </span>
                </div>
                <p class="text-sm text-slate-500">{{ $product->type_label }}@if($product->sku) · {{ $product->sku }}@endif</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @can('update', $product)
                    <a href="{{ route('products.edit', $product) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                        {{ __('Edit') }}
                    </a>
                @endcan
                @can('delete', $product)
                    <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('{{ __('Delete this product?') }}')">
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
                    <h3 class="font-semibold text-slate-900">{{ __('Product Details') }}</h3>
                </div>
                <dl class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Name') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $product->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('SKU') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $product->sku ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Type') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $product->type_label }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Category') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $product->category ?? '—' }}</dd>
                    </div>
                    @if ($product->description)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Description') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 whitespace-pre-line">{{ $product->description }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Pricing') }}</h3>
                </div>
                <dl class="p-6 space-y-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Unit Price') }}</dt>
                        <dd class="mt-1 text-2xl font-bold text-slate-900">{{ $product->formatted_price }}</dd>
                        @if ($product->unit_label)
                            <p class="text-xs text-slate-500 mt-0.5">{{ __('Per :unit', ['unit' => strtolower($product->unit_label)]) }}</p>
                        @endif
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Tax Rate') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ number_format((float) $product->tax_rate, 2) }}%</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Meta') }}</h3>
                </div>
                <dl class="p-6 space-y-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Created By') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $product->creator?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Created') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $product->created_at->format('M j, Y') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
