<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit
        :title="__('Edit Sales Order')"
        :subtitle="$salesOrder->number"
        max-width="6xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('sales_orders'), 'href' => route('sales-orders.index')],
                ['label' => $salesOrder->number, 'href' => route('sales-orders.show', $salesOrder)],
                ['label' => __('Edit'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('sales-orders.update', $salesOrder) }}">
            @csrf
            @method('PUT')
            @include('sales-orders._form', ['salesOrder' => $salesOrder, 'customers' => $customers, 'opportunities' => $opportunities, 'products' => $products])
            <x-forms.footer :cancel-href="route('sales-orders.show', $salesOrder)" :submit-label="__('Save Sales Order')" />
        </form>
    </x-layouts.edit>
</x-app-layout>
