<x-app-layout>
    <x-flash-messages />

    <x-layouts.create
        :title="__('New :label', ['label' => crm_term('invoice')])"
        max-width="6xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('invoices'), 'href' => route('invoices.index')],
                ['label' => __('New :label', ['label' => crm_term('invoice')]), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('invoices.store') }}">
            @csrf
            @include('invoices._form', ['invoice' => $invoice, 'customers' => $customers, 'opportunities' => $opportunities, 'products' => $products, 'sourceQuotation' => $sourceQuotation ?? null])
            <x-forms.footer :cancel-href="route('invoices.index')" :submit-label="__('Create :label', ['label' => crm_term('invoice')])" />
        </form>
    </x-layouts.create>
</x-app-layout>
