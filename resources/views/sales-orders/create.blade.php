<x-app-layout>
    <x-flash-messages />

    <x-layouts.create
        :title="__('New Sales Order')"
        :subtitle="__('Create a sales order with product and service lines')"
        max-width="6xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('sales_orders'), 'href' => route('sales-orders.index')],
                ['label' => __('New Sales Order'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('sales-orders.store') }}">
            @csrf
            @include('sales-orders._form', ['salesOrder' => $salesOrder, 'customers' => $customers, 'opportunities' => $opportunities, 'products' => $products, 'sourceQuotation' => $sourceQuotation ?? null])
            <x-forms.footer :cancel-href="route('sales-orders.index')" :submit-label="__('Create Sales Order')" />
        </form>
    </x-layouts.create>
</x-app-layout>
