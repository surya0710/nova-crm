<x-app-layout>
    <x-flash-messages />

    <x-layouts.create
        :title="__('New Quotation')"
        :subtitle="__('Build a quotation with line items for your customer')"
        max-width="6xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('quotations'), 'href' => route('quotations.index')],
                ['label' => __('New Quotation'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('quotations.store') }}">
            @csrf
            @include('quotations._form', ['quotation' => $quotation, 'customers' => $customers, 'opportunities' => $opportunities, 'products' => $products])
            <x-forms.footer :cancel-href="route('quotations.index')" :submit-label="__('Create Quotation')" />
        </form>
    </x-layouts.create>
</x-app-layout>
