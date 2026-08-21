<x-app-layout>
    <x-flash-messages />
    <x-layouts.entity-listing :title="crm_term('price_lists')" :subtitle="__('Customer-specific and quantity-based pricing')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('price_lists'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            @can('create', App\Models\PriceList::class)
                <x-ui.button :href="route('price-lists.create')" variant="primary" size="sm">{{ __('New price list') }}</x-ui.button>
            @endcan
        </x-slot:actions>
        @if ($priceLists->isEmpty())
            <x-ui.empty-state-preset variant="generic" :title="__('No price lists')" :action-href="auth()->user()->can('create', App\Models\PriceList::class) ? route('price-lists.create') : null" :action-label="__('Create price list')" />
        @else
            <x-tables.table :columns="[__('Name'), __('Currency'), __('Items'), __('Status')]" :sticky="false">
                @foreach ($priceLists as $list)
                    <tr>
                        <td class="px-4 py-3">
                            <a class="font-semibold text-primary-700" href="{{ route('price-lists.show', $list) }}">{{ $list->name }}</a>
                            @if ($list->is_default)<x-ui.badge variant="info">{{ __('Default') }}</x-ui.badge>@endif
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $list->currency }}</td>
                        <td class="px-4 py-3 text-sm">{{ $list->items_count }}</td>
                        <td class="px-4 py-3"><x-ui.badge :variant="$list->status === 'active' ? 'success' : 'neutral'">{{ $list->status_label }}</x-ui.badge></td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif
        @if ($priceLists->hasPages())
            <x-slot:pagination>{{ $priceLists->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
