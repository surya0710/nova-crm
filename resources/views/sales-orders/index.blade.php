@php
    $statusVariant = [
        'draft' => 'neutral',
        'confirmed' => 'info',
        'processing' => 'primary',
        'partially_fulfilled' => 'warning',
        'fulfilled' => 'success',
        'cancelled' => 'danger',
    ];
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Sales Order'),
        __('Customer'),
        ['label' => __('Order Date'), 'class' => 'hidden md:table-cell'],
        ['label' => __('Total'), 'align' => 'right'],
        __('Status'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="crm_term('sales_orders')"
        :subtitle="__('Create and manage sales orders')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('sales_orders'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('create', App\Models\SalesOrder::class)
                <x-ui.button :href="route('sales-orders.create')" variant="primary" size="sm">{{ __('New Sales Order') }}</x-ui.button>
            @endcan
        </x-slot:actions>

        <x-slot:filters>
            <form method="GET" action="{{ route('sales-orders.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <label for="sales-orders-search" class="sr-only">{{ __('Search sales orders') }}</label>
                    <x-forms.input id="sales-orders-search" name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search number, title, customer…') }}" />
                </div>
                <x-forms.select name="status" aria-label="{{ __('Status') }}">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (config('sales_orders.statuses') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <div class="flex gap-2">
                    <x-forms.select name="customer_id" class="flex-1" aria-label="{{ __('Customer') }}">
                        <option value="">{{ __('All customers') }}</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) ($filters['customer_id'] ?? '') === (string) $customer->id)>{{ $customer->display_name }}</option>
                        @endforeach
                    </x-forms.select>
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($salesOrders->isEmpty())
            <x-ui.card>
                @if (! empty($filters['search']) || ! empty($filters['status']) || ! empty($filters['customer_id']))
                    <x-ui.empty-state-preset variant="search" />
                @else
                    <x-ui.empty-state-preset
                        variant="generic"
                        :title="__('No sales orders yet')"
                        :description="__('Create a sales order or convert an accepted quotation.')"
                        :action-href="auth()->user()->can('create', App\Models\SalesOrder::class) ? route('sales-orders.create') : null"
                        :action-label="__('New Sales Order')"
                    />
                @endif
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($salesOrders as $salesOrder)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('sales-orders.show', $salesOrder) }}" class="group block">
                                <p class="text-sm font-semibold text-ink-heading group-hover:text-primary-700">{{ $salesOrder->number }}</p>
                                @if ($salesOrder->title)
                                    <p class="mt-0.5 text-xs text-ink-muted">{{ $salesOrder->title }}</p>
                                @endif
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $salesOrder->customer->display_name }}</td>
                        <td class="px-4 py-3 hidden md:table-cell text-sm text-ink-muted">{{ $salesOrder->order_date->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-right text-sm text-ink-heading">{{ $salesOrder->formatted_total }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$statusVariant[$salesOrder->status] ?? 'neutral'">{{ $salesOrder->status_label }}</x-ui.badge>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($salesOrders->hasPages())
            <x-slot:pagination>
                {{ $salesOrders->links() }}
            </x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
