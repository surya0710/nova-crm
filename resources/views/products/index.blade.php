@php
    $statusVariant = [
        'active' => 'success',
        'inactive' => 'neutral',
    ];
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Product'),
        __('Type'),
        ['label' => __('Price'), 'align' => 'right'],
        __('Status'),
        ['label' => __('Category'), 'class' => 'hidden md:table-cell'],
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="crm_term('products')"
        :subtitle="__('Manage your product and service catalog')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('products'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('create', App\Models\Product::class)
                <x-ui.button :href="route('products.create')" variant="primary" size="sm">{{ __('Add Product') }}</x-ui.button>
            @endcan
        </x-slot:actions>

        <x-slot:filters>
            <form method="GET" action="{{ route('products.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <label for="products-search" class="sr-only">{{ __('Search products') }}</label>
                    <x-forms.input id="products-search" name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search name, SKU, category…') }}" />
                </div>
                <x-forms.select name="status" aria-label="{{ __('Status') }}">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (config('products.statuses') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.select name="type" aria-label="{{ __('Type') }}">
                    <option value="">{{ __('All types') }}</option>
                    @foreach (config('products.types') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <div class="flex gap-2">
                    <x-forms.input name="category" class="flex-1" :value="$filters['category'] ?? ''" placeholder="{{ __('Category') }}" />
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($products->isEmpty())
            <x-ui.card>
                @if (! empty($filters['search']) || ! empty($filters['status']) || ! empty($filters['type']) || ! empty($filters['category']))
                    <x-ui.empty-state-preset variant="search" />
                @else
                    <x-ui.empty-state-preset
                        variant="generic"
                        :title="__('No products yet')"
                        :description="__('Add products and services for quotations and invoices.')"
                        :action-href="auth()->user()->can('create', App\Models\Product::class) ? route('products.create') : null"
                        :action-label="__('Add Product')"
                    />
                @endif
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($products as $product)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('products.show', $product) }}" class="group block">
                                <p class="text-sm font-semibold text-ink-heading group-hover:text-primary-700">{{ $product->name }}</p>
                                <p class="mt-0.5 text-xs text-ink-muted">{{ $product->sku ?? '—' }}</p>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $product->type_label }}</td>
                        <td class="px-4 py-3 text-right text-sm text-ink-heading">
                            {{ $product->formatted_price }}@if($product->unit_label)<span class="font-normal text-ink-muted"> / {{ $product->unit_label }}</span>@endif
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$statusVariant[$product->status] ?? 'neutral'">{{ $product->status_label }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell text-sm text-ink-muted">{{ $product->category ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($products->hasPages())
            <x-slot:pagination>
                {{ $products->links() }}
            </x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
