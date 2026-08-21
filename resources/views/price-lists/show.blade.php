<x-app-layout>
    <x-flash-messages />
    <x-layouts.entity-detail :title="$priceList->name" :subtitle="$priceList->is_default ? __('Default list') : $priceList->status_label">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => crm_term('price_lists'), 'href' => route('price-lists.index')],
                ['label' => $priceList->name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            @can('update', $priceList)
                <x-ui.button :href="route('price-lists.edit', $priceList)" variant="secondary" size="sm">{{ __('Edit') }}</x-ui.button>
            @endcan
            @can('delete', $priceList)
                <form method="POST" action="{{ route('price-lists.destroy', $priceList) }}" onsubmit="return confirm('{{ __('Delete this price list?') }}')">@csrf @method('DELETE')<x-ui.button type="submit" variant="danger" size="sm">{{ __('Delete') }}</x-ui.button></form>
            @endcan
        </x-slot:actions>

        <x-entity.section :title="__('Prices')">
            <x-tables.table :columns="[__('Product'), ['label' => __('Price'), 'align' => 'right'], __('Qty'), __('Effective')]" :sticky="false">
                @foreach ($priceList->items as $item)
                    <tr>
                        <td class="px-4 py-3 text-sm">{{ $item->product?->name }}</td>
                        <td class="px-4 py-3 text-right text-sm">{{ number_format((float) $item->unit_price, 2) }} {{ $priceList->currency }}</td>
                        <td class="px-4 py-3 text-sm">{{ $item->min_quantity }}{{ $item->max_quantity ? '–'.$item->max_quantity : '+' }}</td>
                        <td class="px-4 py-3 text-sm">{{ collect([$item->starts_at?->format('M j'), $item->ends_at?->format('M j, Y')])->filter()->join(' – ') ?: '—' }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
        </x-entity.section>

        @if ($priceList->customers->isNotEmpty())
            <x-entity.section :title="__('Customers')">
                <ul class="list-disc pl-5 text-sm">
                    @foreach ($priceList->customers as $customer)
                        <li><a class="text-primary-600" href="{{ route('customers.show', $customer) }}">{{ $customer->display_name }}</a></li>
                    @endforeach
                </ul>
            </x-entity.section>
        @endif

        @if ($history->isNotEmpty())
            <x-entity.section :title="__('Price history')">
                <x-tables.table :columns="[__('When'), __('Product'), __('From'), __('To'), __('By')]" :sticky="false">
                    @foreach ($history as $row)
                        <tr>
                            <td class="px-4 py-2 text-sm">{{ $row->created_at?->format('M j, Y H:i') }}</td>
                            <td class="px-4 py-2 text-sm">{{ $row->product?->name }}</td>
                            <td class="px-4 py-2 text-sm">{{ $row->old_unit_price !== null ? number_format((float) $row->old_unit_price, 2) : '—' }}</td>
                            <td class="px-4 py-2 text-sm">{{ number_format((float) $row->new_unit_price, 2) }}</td>
                            <td class="px-4 py-2 text-sm">{{ $row->changer?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </x-tables.table>
            </x-entity.section>
        @endif
    </x-layouts.entity-detail>
</x-app-layout>
